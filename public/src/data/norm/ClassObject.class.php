<?php
namespace data\norm;

use data\AbstractData;
use data\InvalidDataException;
use ReflectionClass;
use util\MAPException;

/**
 * This file is part of the MAP-Framework.
 *
 * @author    Michael Piontkowski <mail@mpiontkowski.de>
 * @copyright Copyright 2016 Michael Piontkowski
 * @license   https://raw.githubusercontent.com/map-framework/map/master/LICENSE.txt Apache License 2.0
 */
class ClassObject extends AbstractData {

	const PATTERN_NAME_SPACE = '^([a-zA-Z0-9_]+\\\)*[a-zA-Z0-9_]+';

	/**
	 * @var string
	 */
	private $nameSpace;

	/**
	 * @throws InvalidDataException
	 */
	public function set(string $nameSpace) {
		self::assertIsNameSpace($nameSpace);

		$this->nameSpace = $nameSpace;
	}

	public function get():string {
		return $this->nameSpace;
	}

	final public function exists():bool {
		return class_exists($this->nameSpace);
	}

	/**
	 * @throws ClassNotFoundException
	 */
	final protected function getReflection():ReflectionClass {
		$this->assertExists();

		return new ReflectionClass($this->get());
	}

	/**
	 * @throws ClassNotFoundException
	 */
	final public function isInterface():bool {
		return $this->getReflection()->isInterface();
	}

	/**
	 * @throws ClassNotFoundException
	 */
	final public function isAbstract():bool {
		return $this->getReflection()->isAbstract();
	}

	/**
	 * @throws ClassNotFoundException
	 */
	final public function isFinal():bool {
		return $this->getReflection()->isFinal();
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws MAPException
	 */
	final public function isChildOf(ClassObject $parentClass):bool {
		$parentClass->assertIsNotFinal();
		$parentClass->assertIsNotInterface();

		$reflection = $this->getReflection();
		do {
			if ($reflection->getName() === $parentClass->get()) {
				return true;
			}

			$reflection = $reflection->getParentClass();
		}
		while ($reflection !== false);
		return false;
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws MAPException
	 */
	final public function implementsInterface(ClassObject $interface):bool {
		$interface->assertIsInterface();

		return $this->getReflection()->implementsInterface($interface->get());
	}

	/**
	 * @throws ClassNotFoundException
	 */
	final public function assertExists() {
		if (!$this->exists()) {
			throw new ClassNotFoundException($this);
		}
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws MAPException
	 */
	final public function assertIsInterface() {
		if (!$this->isInterface()) {
			throw (new MAPException('Expected an Interface.'))
					->setData('class', $this);
		}
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws MAPException
	 */
	final public function assertIsNotInterface() {
		if ($this->isInterface()) {
			throw (new MAPException('Expected no Interface.'))
					->setData('class', $this);
		}
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws MAPException
	 */
	final public function assertIsAbstract() {
		if (!$this->isAbstract()) {
			throw (new MAPException('Expected an Abstract-Class.'))
					->setData('class', $this);
		}
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws MAPException
	 */
	final public function assertIsNotAbstract() {
		if ($this->isAbstract()) {
			throw (new MAPException('Expected a Non-Abstract-Class.'))
					->setData('class', $this);
		}
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws MAPException
	 */
	final public function assertIsFinal() {
		if (!$this->isFinal()) {
			throw (new MAPException('Expected a Final-Class.'))
					->setData('class', $this);
		}
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws MAPException
	 */
	final public function assertIsNotFinal() {
		if ($this->isFinal()) {
			throw (new MAPException('Expected a Non-Final-Class.'))
					->setData('class', $this);
		}
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws InstanceException
	 */
	final public function assertIsChildOf(ClassObject $parentClass) {
		if (!$this->isChildOf($parentClass)) {
			throw new InstanceException($this, $parentClass);
		}
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws InstanceException
	 * @throws MAPException
	 */
	final public function assertImplementsInterface(ClassObject $interface) {
		if (!$this->implementsInterface($interface)) {
			throw new InstanceException($this, $interface);
		}
	}

	final public static function isNameSpace(string $nameSpace) {
		return self::isMatching(self::PATTERN_NAME_SPACE, $nameSpace);
	}

	/**
	 * @throws InvalidDataException
	 */
	final public static function assertIsNameSpace(string $nameSpace) {
		self::assertIsMatching(self::PATTERN_NAME_SPACE, $nameSpace);
	}

}