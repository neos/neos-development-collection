<?php
namespace TYPO3\Neos\TypoScript;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\TypoScriptObjects\CaseImplementation;

/**
 * TypoScript object which is used for the "ContentCollection" node type.
 */
class ContentCollectionCaseImplementation extends CaseImplementation {

	/**
	 * The name of the collection node which shall be rendered.
	 *
	 * @var string
	 */
	protected $nodePath;

	/**
	 * Sets the identifier of the collection node which shall be rendered
	 *
	 * @param string $nodePath
	 * @return void
	 */
	public function setNodePath($nodePath) {
		$this->nodePath = $nodePath;
	}

	/**
	 * Returns the identifier of the collection node which shall be rendered
	 *
	 * @return string
	 */
	public function getNodePath() {
		return $this->tsValue('nodePath');
	}

	/**
	 * Evaluate the collection nodes
	 *
	 * @return string
	 */
	public function evaluate() {
		$this->tsRuntime->pushContext('nodePath', $this->getNodePath());
		$output = parent::evaluate();
		$this->tsRuntime->popContext();
		return $output;
	}
}
?>