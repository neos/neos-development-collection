<?php
namespace TYPO3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The TypoScript "JavaScript" object
 *
 * @FLOW3\Scope("prototype")
 */
class JavaScript extends \TYPO3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3.TYPO3/Private/Templates/TypoScriptObjects/JavaScript.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('source', 'inline');

	/**
	 * @var string
	 */
	protected $source;

	/**
	 * @var string
	 */
	protected $inline;

	/**
	 *
	 * @return string The source
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * @param string $source The JavaScript source as an URL
	 * @return void
	 */
	public function setSource($source) {
		$this->source = $source;
	}

	/**
	 * @return string The inline JavaScript content
	 */
	public function getInline() {
		return $this->inline;
	}

	/**
	 * @param string $inline The inline JavaScript content
	 * @return void
	 */
	public function setInline($inline) {
		$this->inline = $inline;
	}

}
?>