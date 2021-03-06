<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core;

use React\EventLoop\LoopInterface;
use WildPHP\Core\Logger\Logger;

class ComponentContainer
{
	/**
	 * @var LoopInterface
	 **/
	protected $loop = null;

	/**
	 * @var object[]
	 */
	protected $storedComponents = [];

	/**
	 * @param $object
	 */
	public function store($object)
	{
		$this->storedComponents[get_class($object)] = $object;
	}

	/**
	 * @param string $className
	 *
	 * @return object
	 */
	public function retrieve(string $className)
	{
		if (!array_key_exists($className, $this->storedComponents))
			throw new \InvalidArgumentException('Could not retrieve object from container: ' . $className);

		return $this->storedComponents[$className];
	}

	/**
	 * @return LoopInterface
	 */
	public function getLoop(): LoopInterface
	{
		return $this->loop;
	}

	/**
	 * @param LoopInterface $loop
	 */
	public function setLoop(LoopInterface $loop)
	{
		$this->loop = $loop;
	}
}