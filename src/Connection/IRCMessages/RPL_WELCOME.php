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
 * Syntax: :server 001 nickname :greeting
 */
class RPL_WELCOME
{
	use NicknameTrait;

	protected static $verb = '001';

	protected $server = '';

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

		$nickname = $incomingIrcMessage->getArgs()[0];
		$server = $incomingIrcMessage->getPrefix();
		$object = new self();
		$object->setNickname($nickname);
		$object->setServer($server);

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
}