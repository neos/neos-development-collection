<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Reflection\ObjectAccess;


/**
 * Base class for all TypoScript objects
 */
abstract class AbstractTypoScriptObject {

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
	 * Name of this TypoScript object, like TYPO3.TYPO3:Text
	 *
	 * @var string
	 */
	protected $typoScriptObjectName;

	/**
	 * List of processors, purely internal.
	 *
	 * @var array
	 * @internal
	 */
	protected $internalProcessors = array();

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
	 * @param array $internalProcessors
	 * @internal
	 */
	public function setInternalProcessors(array $internalProcessors) {
		$this->internalProcessors = $internalProcessors;
	}

	/**
	 * @return array
	 * @internal
	 */
	public function getInternalProcessors() {
		return $this->internalProcessors;
	}

	/**
	 * Return the typoscript value relative to this TypoScript object (with processors
	 * etc applied)
	 *
	 * @param string $path
	 * @return mixed
	 */
	protected function tsValue($path) {
		$pathParts = explode('.', $path);
		$firstPathPart = array_shift($pathParts);

		try {
			// TODO: this code does not work yet if the TS object implements ArrayAccess
			$value = ObjectAccess::getProperty($this, $firstPathPart, TRUE);
		} catch (\TYPO3\FLOW3\Reflection\Exception\PropertyNotAccessibleException $e) {
			$value = NULL;
			$pathParts = array();
		}

		if (count($pathParts) > 0) {
			$remainingPath = implode('.', $pathParts);
			$value = ObjectAccess::getPropertyPath($value, $remainingPath);
		}
		return $this->tsRuntime->evaluateProcessor($path, $this, $value);
	}
}
?>