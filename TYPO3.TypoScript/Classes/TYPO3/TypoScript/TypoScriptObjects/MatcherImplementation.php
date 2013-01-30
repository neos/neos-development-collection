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
 * Matcher object for use inside a "Case" statement
 */
class MatcherImplementation extends AbstractTypoScriptObject {

	/**
	 * @var boolean
	 */
	protected $condition;

	/**
	 * The type to render if $condition is TRUE
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * A path to a TypoScript configuration
	 *
	 * @var string
	 */
	protected $renderPath;

	/**
	 * @param boolean $condition
	 */
	public function setCondition($condition) {
		$this->condition = $condition;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @param string $renderPath
	 */
	public function setRenderPath($renderPath) {
		$this->renderPath = $renderPath;
	}

	/**
	 * If $condition matches, render $type and return it. Else, return MATCH_NORESULT.
	 *
	 * @return mixed
	 */
	public function evaluate() {
		if ($this->tsValue('condition')) {
			$renderPath = $this->tsValue('renderPath');
			if ($renderPath !== NULL) {
				$renderedElement = $this->tsRuntime->render($renderPath);
			} else {
				$type = $this->tsValue('type');
				$renderedElement = $this->tsRuntime->render(
					sprintf('%s/element<%s>', $this->path, $type)
				);
				$renderedElement = $this->tsRuntime->evaluateProcessor('element', $this, $renderedElement);
			}
			return $renderedElement;
		} else {
			return CaseImplementation::MATCH_NORESULT;
		}
	}
}
?>