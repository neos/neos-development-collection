<?php
namespace TYPO3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A TypoScript Node object
 *
 * @FLOW3\Scope("prototype")
 */
class Node extends \TYPO3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3.TYPO3/Private/Templates/TypoScriptObjects/Node.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('properties');

	/**
	 * A copy of the node's properties
	 *
	 * @var array
	 */
	protected $properties = array();

	/**
	 * Sets the node the TypoScript object is based on.
	 * All available properties of the node will be registered as presentation model
	 * properties.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node The node the TypoScript object is based on
	 * @return void
	 */
	public function setNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		parent::setNode($node);
		$this->properties = $node->getProperties();

		$contentType = $node->getContentType();
		if (strpos($contentType, ':') !== FALSE) {
			list($packageKey, $typeName) = explode(':', $node->getContentType());
		} else {
			$packageKey = 'TYPO3.TYPO3';
			$typeName = $contentType;
		}

		$possibleTemplateSource = 'resource://' . $packageKey . '/Private/Templates/TypoScriptObjects/' . $typeName . '.html';
		if (file_exists($possibleTemplateSource)) {
			$this->templateSource = $possibleTemplateSource;
			$this->template->setSource($this->templateSource);
		}
	}

	/**
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @param array $properties
	 * @return void
	 */
	public function setProperties(array $properties) {
		$this->properties = $properties;
	}

	/**
	 * Returns the rendered content of this content object
	 *
	 * @return string The rendered content as a string - usually (X)HTML, XML or just plain text
	 */
	public function render() {
		return parent::render();
	}
}
?>