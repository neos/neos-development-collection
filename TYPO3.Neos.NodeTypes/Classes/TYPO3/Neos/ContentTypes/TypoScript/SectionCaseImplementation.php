<?php
namespace TYPO3\Neos\ContentTypes\TypoScript;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Neos.ContentTypes".     *
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
 * TypoScript object which is used for the "Section" content type.
 */
class SectionCaseImplementation extends CaseImplementation {

	/**
	 * The name of the section Node which shall be rendered.
	 *
	 * @var string
	 */
	protected $nodePath;

	/**
	 * Sets the identifier of the section node which shall be rendered
	 *
	 * @param string $nodePath
	 * @return void
	 */
	public function setNodePath($nodePath) {
		$this->nodePath = $nodePath;
	}

	/**
	 * Returns the identifier of the section node which shall be rendered
	 *
	 * @return string
	 */
	public function getNodePath() {
		return $this->tsValue('nodePath');
	}

	/**
	 * Evaluate the section nodes
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