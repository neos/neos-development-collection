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
 * The TypoScript "Stylesheet" object
 *
 * @FLOW3\Scope("prototype")
 */
class Stylesheet extends \TYPO3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3.TYPO3/Private/Templates/TypoScriptObjects/Stylesheet.html';

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
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * @param string $source The Stylesheet source as an URL
	 * @return void
	 */
	public function setSource($source) {
		$this->source = $source;
	}

	/**
	 * @return string The inline Stylesheet content
	 */
	public function getInline() {
		return $this->inline;
	}

	/**
	 * @param string $inline The inline Stylesheet content
	 * @return void
	 */
	public function setInline($inline) {
		$this->inline = $inline;
	}

	/**
	 * @return string The stylesheet media type
	 */
	public function getMedia() {
		return $this->media;
	}

	/**
	 * @param string $media The stylesheet media type
	 * @return void
	 */
	public function setMedia($media) {
		$this->media = $media;
	}
}
?>