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
 * A TypoScript Node object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Node extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/Templates/TypoScriptObjects/Node.html';

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
	 * @param \F3\TYPO3CR\Domain\Model\Node $node The node the TypoScript object is based on
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNode(\F3\TYPO3CR\Domain\Model\Node $node) {
		parent::setNode($node);
		$this->properties = $node->getProperties();

		$contentType = $node->getContentType();
		if (strpos($contentType, ':') !== FALSE) {
			list($packageKey, $typeName) = explode(':', $node->getContentType());
		} else {
			$packageKey = 'TYPO3';
			$typeName = $contentType;
		}

		$possibleTemplateSource = 'resource://' . $packageKey . '/Private/Templates/TypoScriptObjects/' . $typeName . '.html';
		if (file_exists($possibleTemplateSource)) {
			$this->templateSource = $possibleTemplateSource;
			$this->template->setSource($this->templateSource);
		}
	}

	public function getProperties() {
		return $this->properties;
	}

	public function setProperties(array $properties) {
		$this->properties = $properties;
	}

	/**
	 * Returns the rendered content of this content object
	 *
	 * @return string The rendered content as a string - usually (X)HTML, XML or just plain text
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		return parent::render();
	}
}
?>