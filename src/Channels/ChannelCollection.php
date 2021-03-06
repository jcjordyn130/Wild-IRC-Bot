<?php

/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Channels;

use Collections\Collection;
use WildPHP\Core\ComponentContainer;
use WildPHP\Core\ComponentTrait;
use WildPHP\Core\Configuration\Configuration;
use WildPHP\Core\Connection\Queue;
use WildPHP\Core\ContainerTrait;
use WildPHP\Core\Users\User;
use WildPHP\Core\Users\UserCollection;

class ChannelCollection extends Collection
{
	use ComponentTrait;
	use ContainerTrait;

	/**
	 * ChannelCollection constructor.
	 *
	 * @param ComponentContainer $container
	 */
	public function __construct(ComponentContainer $container)
	{
		parent::__construct(Channel::class);
		$this->setContainer($container);
	}

	/**
	 * Creates a fake channel with the bot and another user in it, to allow private conversations to happen.
	 *
	 * @param User $user
	 * @param bool $sendWhox
	 *
	 * @return Channel
	 */
	public function createFakeConversationChannel(User $user, $sendWhox = true)
	{
		$userCollection = new UserCollection($this->getContainer());
		$channelModes = new ChannelModes($this->getContainer());
		$channel = new Channel($userCollection, $channelModes);
		$channel->setName($user->getNickname());
		$channel->getUserCollection()
			->add($user);
		$channel->getUserCollection()
			->add(UserCollection::fromContainer($this->getContainer())
				->getSelf());
		$this->add($channel);

		if ($sendWhox)
			Queue::fromContainer($this->getContainer())
				->who($user->getNickname(), '%nuhaf');

		return $channel;
	}

	/**
	 * This function is different from the findByChannelName
	 * function in that it will always return a channel object.
	 *
	 * @param string $name
	 * @param User|null $user
	 *
	 * @return Channel
	 */
	public function requestByChannelName(string $name, User $user = null): Channel
	{
		$ownNickname = Configuration::fromContainer($this->getContainer())
			->get('currentNickname')
			->getValue();

		$conversationChannel = $user && $ownNickname == $name;
		$channelName = $conversationChannel ? $user->getNickname() : $name;

		if ($this->containsChannelName($channelName))
			return $this->findByChannelName($channelName);

		if ($conversationChannel && !$this->findByChannelName($channelName))
			return $this->createFakeConversationChannel($user);

		$userCollection = new UserCollection($this->getContainer());
		$channelModes = new ChannelModes($this->getContainer());
		$channel = new Channel($userCollection, $channelModes);
		$channel->setName($name);
		$this->add($channel);

		return $channel;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function containsChannelName(string $name): bool
	{
		return !empty($this->findByChannelName($name));
	}

	/**
	 * @param string $name
	 *
	 * @return false|Channel
	 */
	public function findByChannelName(string $name)
	{
		return $this->find(function (Channel $channel) use ($name)
		{
			return $channel->getName() == $name;
		});
	}
}