<?php
namespace TYPO3\TypoScript\TypoScriptObjects\Helpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A proxy object representing a TypoScript path inside a Fluid Template. It allows
 * to render arbitrary TypoScript objects or Eel expressions using the already-known
 * property path syntax.
 *
 * It wraps a part of the TypoScript tree which does not contain TypoScript objects or Eel expressions.
 *
 * This class is instanciated inside TemplateImplementation and is never used outside.
 */
class TypoScriptPathProxy implements \TYPO3\Fluid\Core\Parser\SyntaxTree\TemplateObjectAccessInterface, \ArrayAccess, \IteratorAggregate, \Countable {

	/**
	 * Reference to the TypoScript Runtime which controls the whole rendering
	 *
	 * @var \TYPO3\TypoScript\Core\Runtime
	 */
	protected $tsRuntime;

	/**
	 * Reference to the "parent" TypoScript object
	 *
	 * @var \TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation
	 */
	protected $templateImplementation;

	/**
	 * The TypoScript path this object proxies
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * This is a part of the TypoScript tree built when evaluating $this->path.
	 *
	 * @var array
	 */
	protected $partialTypoScriptTree;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation $templateImplementation
	 * @param string $path
	 * @param array $partialTypoScriptTree
	 */
	public function __construct(\TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation $templateImplementation, $path, array $partialTypoScriptTree) {
		$this->templateImplementation = $templateImplementation;
		$this->tsRuntime = $templateImplementation->getTsRuntime();
		$this->path = $path;
		$this->partialTypoScriptTree = $partialTypoScriptTree;
	}

	/**
	 * TRUE if a given subpath exists, FALSE otherwise.
	 *
	 * @param string $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return isset($this->partialTypoScriptTree[$offset]);
	}

	/**
	 * Return the object at $offset; evaluating simple types right away, and
	 * wrapping arrays into ourselves again.
	 *
	 * @param string $offset
	 * @return mixed|\TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptPathProxy
	 */
	public function offsetGet($offset) {
		if (!isset($this->partialTypoScriptTree[$offset])) {
			return NULL;
		}
		if (!is_array($this->partialTypoScriptTree[$offset])) {
				// Simple type; we call "evaluate" nevertheless to make sure processors are applied.
			return $this->tsRuntime->evaluate($this->path . '/' . $offset);
		} else {
				// arbitrary array (could be Eel expression, TypoScript object, nested sub-array) again, so we wrap it with ourselves.
			return new TypoScriptPathProxy($this->templateImplementation, $this->path . '/' . $offset, $this->partialTypoScriptTree[$offset]);
		}
	}

	/**
	 * Stub to implement the ArrayAccess interface cleanly
	 *
	 * @param string $offset
	 * @param mixed $value
	 * @throws \TYPO3\TypoScript\Exception\UnsupportedProxyMethodException
	 */
	public function offsetSet($offset, $value) {
		throw new \TYPO3\TypoScript\Exception\UnsupportedProxyMethodException('Setting a property of a path proxy not supported. (tried to set: ' . $this->path . ' -- ' . $offset . ')', 1372667221);
	}

	/**
	 * Stub to implement the ArrayAccess interface cleanly
	 *
	 * @param string $offset
	 * @throws \TYPO3\TypoScript\Exception\UnsupportedProxyMethodException
	 */
	public function offsetUnset($offset) {
		throw new \TYPO3\TypoScript\Exception\UnsupportedProxyMethodException('Unsetting a property of a path proxy not supported. (tried to unset: ' . $this->path . ' -- ' . $offset . ')', 1372667331);
	}

	/**
	 * Post-Processor which is called whenever this object is encountered in a Fluid
	 * object access.
	 *
	 * Evaluates TypoScript objects and eel expressions.
	 *
	 * @return \TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptPathProxy|mixed
	 */
	public function objectAccess() {
		if (isset($this->partialTypoScriptTree['__objectType'])) {
			return $this->tsRuntime->evaluate($this->path);
		} elseif (isset($this->partialTypoScriptTree['__eelExpression'])) {
			return $this->tsRuntime->evaluate($this->path, $this->templateImplementation);
		}

		return $this;
	}

	/**
	 * Iterates through all subelements.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		$evaluatedArray = array();
		foreach ($this->partialTypoScriptTree as $key => $value) {
			if (!is_array($value)) {
				$evaluatedArray[$key] = $value;
			} elseif (isset($value['__objectType'])) {
				$evaluatedArray[$key] = $this->tsRuntime->evaluate($this->path . '/' . $key);
			} elseif (isset($value['__eelExpression'])) {
				$evaluatedArray[$key] = $this->tsRuntime->evaluate($this->path . '/' . $key, $this->templateImplementation);
			} else {
				$evaluatedArray[$key] = new TypoScriptPathProxy($this->templateImplementation, $this->path . '/' . $key, $this->partialTypoScriptTree[$key]);
			}
		}
		return new \ArrayIterator($evaluatedArray);
	}

	/**
	 * @return integer
	 */
	public function count() {
		return count($this->partialTypoScriptTree);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		if ($this->count() === 1 && isset($this->partialTypoScriptTree['__simpleValue'])) {
			return (string)$this->tsRuntime->evaluate($this->path);
		}
		return '';
	}
}
?>