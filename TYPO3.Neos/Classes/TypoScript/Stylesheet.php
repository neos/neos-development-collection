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
 * The TypoScript "Stylesheet" object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Stylesheet extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/TypoScript/Templates/Stylesheet.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('source', 'inline', 'media');

	/**
	 * @var string
	 */
	protected $source;

	/**
	 * @var string
	 */
	protected $inline;

	/**
	 * @var string
	 */
	protected $media = 'all';

	/**
	 *
	 * @return string The source
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * @param string $source The Stylesheet source as an URL
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setSource($source) {
		$this->source = $source;
	}

	/**
	 * @return string The inline Stylesheet content
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getInline() {
		return $this->inline;
	}

	/**
	 * @param string $inline The inline Stylesheet content
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setInline($inline) {
		$this->inline = $inline;
	}

	/**
	 * @return string The stylesheet media type
	 * @author Christopher Hlubek <hlubek@networkteam.com> 
	 */
	public function getMedia() {
		return $this->media;
	}

	/**
	 * @param string $inline The stylesheet media type
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setMedia($media) {
		$this->media = $media;
	}
}
?>