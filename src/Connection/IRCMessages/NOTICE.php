<?php

/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Connection\IRCMessages;

use WildPHP\Core\Connection\IncomingIrcMessage;
use WildPHP\Core\Connection\UserPrefix;

/**
 * Class NOTICE
 * @package WildPHP\Core\Connection\IRCMessages
 *
 * Syntax: prefix NOTICE #channel :message
 */
class NOTICE implements ReceivableMessage, SendableMessage
{
	use PrefixTrait;
	use ChannelTrait;
	use NicknameTrait;
	use MessageTrait;

	/**
	 * @var string
	 */
	protected static $verb = 'NOTICE';

	public function __construct(string $channel, string $message)
	{
		$this->setChannel($channel);
		$this->setMessage($message);
	}

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

		$prefix = UserPrefix::fromIncomingIrcMessage($incomingIrcMessage);
		$channel = $incomingIrcMessage->getArgs()[0];
		$message = $incomingIrcMessage->getArgs()[1];

		$object = new self($channel, $message);
		$object->setPrefix($prefix);
		$object->setNickname($prefix->getNickname());

		return $object;
	}

	public function __toString()
	{
		return 'NOTICE ' . $this->getChannel() . ' :' . $this->getMessage() . "\r\n";
	}
}