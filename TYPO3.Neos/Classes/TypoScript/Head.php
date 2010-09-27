<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The TypoScript "Head" object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Head extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/TypoScript/Templates/Head.html';

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
	 * @var array<\F3\TYPO3\TypoScript\JavaScript>
	 */
	protected $javaScripts = array();

	/**
	 * @var array<\F3\TYPO3\TypoScript\Stylesheet>
	 */
	protected $stylesheets = array();

	/**
	 * Overrides the title of this page.
	 *
	 * @param string $title
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the overriden title of this page.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
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