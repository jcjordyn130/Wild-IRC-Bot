<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Connection\IRCMessages;

use WildPHP\Core\Connection\IncomingIrcMessage;

/**
 * Class RPL_WELCOME
 * @package WildPHP\Core\Connection\IRCMessages
 *
 * Syntax: :server 005 nickname VARIABLE[=key] VARIABLE[=key] ... :greeting
 */
class RPL_ISUPPORT implements ReceivableMessage
{
	use NicknameTrait;

	protected static $verb = '005';

	protected $server = '';

	protected $variables = [];

	/**
	 * @param IncomingIrcMessage $incomingIrcMessage
	 *
	 * @return \self
	 * @throws \InvalidArgumentException
	 */
	public static function fromIncomingIrcMessage(IncomingIrcMessage $incomingIrcMessage): self
	{
		if ($incomingIrcMessage->getVerb() != self::$verb)
			throw new \InvalidArgumentException('Expected incoming ' . self::$verb . '; got ' . $incomingIrcMessage->getVerb());

		$args = $incomingIrcMessage->getArgs();
		$nickname = array_shift($args);
		$server = $incomingIrcMessage->getPrefix();

		$object = new self();
		$object->setNickname($nickname);
		$object->setServer($server);
		$object->setVariables($args);

		return $object;
	}

	/**
	 * @return string
	 */
	public function getServer(): string
	{
		return $this->server;
	}

	/**
	 * @param string $server
	 */
	public function setServer(string $server)
	{
		$this->server = $server;
	}

	/**
	 * @return array
	 */
	public function getVariables(): array
	{
		return $this->variables;
	}

	/**
	 * @param array $variables
	 */
	public function setVariables(array $variables)
	{
		$this->variables = $variables;
	}
}