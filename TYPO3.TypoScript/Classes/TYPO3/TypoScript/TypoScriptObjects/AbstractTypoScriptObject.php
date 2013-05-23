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
use TYPO3\Flow\Reflection\ObjectAccess;


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
	 * Name of this TypoScript object, like TYPO3.Neos:Text
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
		return $this->tsRuntime->evaluate($this->path . '/' . $path, $this);
	}
}
?>