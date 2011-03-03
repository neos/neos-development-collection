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
 * The TypoScript "Html" object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Html extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/Templates/TypoScriptObjects/Html.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('source');

	/**
	 * @var string
	 */
	protected $source;

	/**
	 * Overrides the source of this element.
	 *
	 * @param string $title
	 * @return void
	 */
	public function setSource($source) {
		$this->source = $source;
	}

	/**
	 * Returns the overriden source of this element.
	 *
	 * @return string
	 */
	public function getSource() {
		return $this->source;
	}

}

?>