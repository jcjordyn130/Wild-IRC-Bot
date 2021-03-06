<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Commands;


class Command
{
	/**
	 * @var callable
	 */
	protected $callback;

	/**
	 * @var CommandHelp
	 */
	protected $help = null;

	/**
	 * @var int
	 */
	protected $minimumArguments = -1;

	/**
	 * @var int
	 */
	protected $maximumArguments = -1;

	/**
	 * @var string
	 */
	protected $requiredPermission = '';

	/**
	 * @return callable
	 */
	public function getCallback(): callable
	{
		return $this->callback;
	}

	/**
	 * @param callable $callback
	 */
	public function setCallback(callable $callback)
	{
		$this->callback = $callback;
	}

	/**
	 * @return CommandHelp|null
	 */
	public function getHelp(): ?CommandHelp
	{
		return $this->help;
	}

	/**
	 * @param CommandHelp $help
	 */
	public function setHelp(CommandHelp $help)
	{
		$this->help = $help;
	}

	/**
	 * @return int
	 */
	public function getMinimumArguments(): int
	{
		return $this->minimumArguments;
	}

	/**
	 * @param int $minimumArguments
	 */
	public function setMinimumArguments(int $minimumArguments)
	{
		$this->minimumArguments = $minimumArguments;
	}

	/**
	 * @return int
	 */
	public function getMaximumArguments(): int
	{
		return $this->maximumArguments;
	}

	/**
	 * @param int $maximumArguments
	 */
	public function setMaximumArguments(int $maximumArguments)
	{
		$this->maximumArguments = $maximumArguments;
	}

	/**
	 * @return string
	 */
	public function getRequiredPermission(): string
	{
		return $this->requiredPermission;
	}

	/**
	 * @param string $requiredPermission
	 */
	public function setRequiredPermission(string $requiredPermission)
	{
		$this->requiredPermission = $requiredPermission;
	}


}