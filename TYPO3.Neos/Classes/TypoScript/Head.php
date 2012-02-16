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
 * The TypoScript "Head" object
 *
 * @FLOW3\Scope("prototype")
 */
class Head extends \TYPO3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3.TYPO3/Private/Templates/TypoScriptObjects/Head.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('title', 'javaScripts', 'stylesheets');

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var array<\TYPO3\TYPO3\TypoScript\JavaScript>
	 */
	protected $javaScripts = array();

	/**
	 * @var array<\TYPO3\TYPO3\TypoScript\Stylesheet>
	 */
	protected $stylesheets = array();

	/**
	 * Overrides the title of this page.
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the overriden title of this page.
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return array
	 */
	public function getJavaScripts() {
		return $this->javaScripts;
	}

	/**
	 * @param array $javaScripts
	 * @return void
	 */
	public function setJavaScripts(array $javaScripts) {
		$this->javaScripts = $javaScripts;
	}

	/**
	 * @return array
	 */
	public function getStylesheets() {
		return $this->stylesheets;
	}

	/**
	 * @param array $stylesheets
	 * @return void
	 */
	public function setStylesheets(array $stylesheets) {
		$this->stylesheets = $stylesheets;
	}

}
?>