<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;


/**
 * Base class for all TypoScript objects
 */
abstract class AbstractTypoScriptObject implements \ArrayAccess {

	/**
	 * @var \TYPO3\TypoScript\Core\Runtime
	 */
	protected $tsRuntime;

	/**
	 * The TypoScript path currently being rendered
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Name of this TypoScript object, like TYPO3.Neos:Text
	 *
	 * @var string
	 */
	protected $typoScriptObjectName;

	/**
	 * List of properties which have been set using array access. We store this for *every* TypoScript object
	 * in order to do things like:
	 * x = Foo {
	 *   a = 'foo'
	 *   b = ${this.a + 'bar'}
	 * }
	 *
	 * @var array
	 * @internal
	 */
	protected $properties = array();

	/**
	 * Constructor
	 *
	 * @param \TYPO3\TypoScript\Core\Runtime $tsRuntime
	 * @param string $path
	 * @param string $typoScriptObjectName
	 */
	public function __construct(\TYPO3\TypoScript\Core\Runtime $tsRuntime, $path, $typoScriptObjectName) {
		$this->tsRuntime = $tsRuntime;
		$this->path = $path;
		$this->typoScriptObjectName = $typoScriptObjectName;
	}

	/**
	 * Evaluate this TypoScript object and return the result
	 *
	 * @return mixed
	 */
	abstract public function evaluate();

	/**
	 * Return the typoscript value relative to this TypoScript object (with processors
	 * etc applied)
	 *
	 * @param string $path
	 * @return mixed
	 */
	protected function tsValue($path) {
		return $this->tsRuntime->evaluate($this->path . '/' . $path, $this);
	}

	/**
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return isset($this->properties[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->tsValue($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->properties[$offset] = $value;
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->properties[$offset]);
	}
}
?>