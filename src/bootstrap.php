<?php
/**
 * WildPHP - an advanced and easily extensible IRC bot written in PHP
 * Copyright (C) 2017 WildPHP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use React\EventLoop\Factory as LoopFactory;
use WildPHP\Core\Commands\CommandHandler;
use WildPHP\Core\Configuration\Configuration;
use WildPHP\Core\Configuration\ConfigurationItem;
use WildPHP\Core\Connection\CapabilityHandler;
use WildPHP\Core\Connection\IrcConnection;
use WildPHP\Core\Connection\Parser;
use WildPHP\Core\Connection\PingPongHandler;
use WildPHP\Core\Connection\Queue;
use WildPHP\Core\DataStorage\DataStorageFactory;
use WildPHP\Core\EventEmitter;
use WildPHP\Core\Logger\Logger;
use WildPHP\Core\Security\PermissionGroup;
use WildPHP\Core\Tasks\TaskController;

/**
 * @param Configuration $configuration
 *
 * @return Logger
 */
function setupLogger(Configuration $configuration): Logger
{
	try
	{
		$logLevel = $configuration->get('loglevel')
			->getValue();

		if (!in_array($logLevel, ['debug', 'info', 'warning', 'error']))
			$logLevel = 'info';
	}
	catch (\Exception $e)
	{
		$logLevel = 'info';
	}
	$klogger = new \Katzgrau\KLogger\Logger(WPHP_ROOT_DIR . '/logs', $logLevel);

	return new Logger($klogger);
}

/**
 * @return Configuration
 */
function setupConfiguration()
{
	$neonBackend = new \WildPHP\Core\Configuration\NeonBackend(WPHP_ROOT_DIR . '/config.neon');

	$configuration = new Configuration($neonBackend);
	$rootdir = dirname(dirname(__FILE__));
	$configuration->set(new ConfigurationItem('rootdir', $rootdir));

	return $configuration;
}

/**
 * @return EventEmitter
 */
function setupEventEmitter()
{
	return new EventEmitter();
}

/**
 * @return \WildPHP\Core\Security\PermissionGroupCollection
 */
function setupPermissionGroupCollection()
{
	$globalPermissionGroup = new \WildPHP\Core\Security\PermissionGroupCollection();

	$dataStorage = DataStorageFactory::getStorage('permissiongroups');

	$groupsToLoad = $dataStorage->getKeys();
	foreach ($groupsToLoad as $group)
	{
		$pGroup = new PermissionGroup($group, true);
		$globalPermissionGroup->add($pGroup);
	}

	register_shutdown_function(function () use ($globalPermissionGroup)
	{
		/** @var PermissionGroup[] $groups */
		$groups = $globalPermissionGroup->toArray();
		$groupList = [];

		foreach ($groups as $group)
		{
			$groupList[] = $group->getName();
		}

		$dataStorage = DataStorageFactory::getStorage('permissiongrouplist');
		$dataStorage->set('groupstoload', $groupList);
	});

	return $globalPermissionGroup;
}

/**
 * @param \WildPHP\Core\ComponentContainer $container
 * @param array $connectionDetails
 *
 * @return IrcConnection
 */
function setupIrcConnection(\WildPHP\Core\ComponentContainer $container, array $connectionDetails)
{
	$loop = $container->getLoop();
	$connectorFactory = new \WildPHP\Core\Connection\ConnectorFactory($loop);

	if ($connectionDetails['secure'])
		$connector = $connectorFactory->createSecure();
	else
		$connector = $connectorFactory->create();

	$ircConnection = new IrcConnection($container);
	$queue = new Queue($container);
	$container->store($queue);
	$ircConnection->registerQueueFlusher($loop, $queue);
	new Parser($container);
	$pingPongHandler = new PingPongHandler($container);
	$pingPongHandler->registerPingLoop($loop, $queue);

	$username = $connectionDetails['user'];
	$hostname = gethostname();
	$server = $connectionDetails['server'];
	$port = $connectionDetails['port'];
	$realname = $connectionDetails['realname'];
	$nickname = $connectionDetails['nick'];
	$password = $connectionDetails['password'] ?? '';

	$ircConnection->createFromConnector($connector, $server, $port);

	EventEmitter::fromContainer($container)
		->on('stream.created',
			function (Queue $queue) use ($username, $hostname, $server, $realname, $nickname, $password)
			{
				$queue->user($username, $hostname, $server, $realname);
				$queue->nick($nickname);

				if (!empty($password))
					$queue->pass($password);
			});

	EventEmitter::fromContainer($container)
		->on('stream.closed',
			function () use ($loop)
			{
				$loop->stop();
			});

	return $ircConnection;
}

/**
 * @param \React\EventLoop\LoopInterface $loop
 * @param Configuration $configuration
 * @param array $connectionDetails
 */
function createNewInstance(\React\EventLoop\LoopInterface $loop, Configuration $configuration, array $connectionDetails)
{
	$componentContainer = new \WildPHP\Core\ComponentContainer();
	$componentContainer->setLoop($loop);
	$componentContainer->store(setupEventEmitter());
	$logger = setupLogger($configuration);
	$componentContainer->store($logger);
	$componentContainer->store($configuration);
	$logger->info('WildPHP initializing');

	$capabilityHandler = new CapabilityHandler($componentContainer);
	$componentContainer->store($capabilityHandler);
	$sasl = new \WildPHP\Core\Connection\SASL($componentContainer);
	$capabilityHandler->setSasl($sasl);
	$componentContainer->store(new CommandHandler($componentContainer, new \Collections\Dictionary()));
	$componentContainer->store(new TaskController($componentContainer));

	$componentContainer->store(new \WildPHP\Core\Channels\ChannelCollection($componentContainer));
	$componentContainer->store(new \WildPHP\Core\Users\UserCollection($componentContainer));
	$componentContainer->store(setupPermissionGroupCollection());
	$componentContainer->store(setupIrcConnection($componentContainer, $connectionDetails));
	$componentContainer->store(new \WildPHP\Core\Security\Validator($componentContainer));

	new \WildPHP\Core\Channels\ChannelStateManager($componentContainer);
	new \WildPHP\Core\Users\UserStateManager($componentContainer);
	new \WildPHP\Core\Commands\HelpCommand($componentContainer);
	new \WildPHP\Core\Security\PermissionCommands($componentContainer);
	new \WildPHP\Core\Management\ManagementCommands($componentContainer);
	new WildPHP\Core\Moderation\ModerationCommands($componentContainer);
	new \WildPHP\Core\Users\BotStateManager($componentContainer);

	try
	{
		$modules = Configuration::fromContainer($componentContainer)
			->get('modules')
			->getValue();

		foreach ($modules as $module)
		{
			try
			{
				new $module($componentContainer);
				$logger->info('Loaded module with class ' . $module);
			}
			catch (\Exception $e)
			{
				$logger->error('Could not properly load module; stability not guaranteed!',
					[
						'class' => $module,
						'message' => $e->getMessage()
					]);
			}

		}
	}
	catch (\WildPHP\Core\Configuration\ConfigurationItemNotFoundException $e)
	{
		echo $e->getMessage();
	}

	$logger->info('A connection has been set up successfully and will be started. This may take a while.', [
		'server' => $connectionDetails['server'] . ':' . $connectionDetails['port'],
		'wantedNickname' => $connectionDetails['nick']
	]);
}

$loop = LoopFactory::create();
$configuration = setupConfiguration();

$connections = $configuration->get('connections')
	->getValue();

foreach ($connections as $connection)
{
	createNewInstance($loop, $configuration, $connection);
}

$loop->run();