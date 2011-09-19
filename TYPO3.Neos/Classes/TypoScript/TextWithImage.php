<?php
namespace TYPO3\TYPO3\TypoScript;

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
 * Text with image
 *
 * @scope prototype
 */
class TextWithImage extends \TYPO3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3.TYPO3/Private/Templates/TypoScriptObjects/TextWithImage.html';

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
	 * @inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Sets the node the TypoScript object is based on.
	 * All available properties of the node will be registered as presentation model
	 * properties.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node The node the TypoScript object is based on
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		parent::setNode($node);
		$this->properties = $node->getProperties();
		if (isset($this->properties['image'])) {
			$this->properties['image'] = $this->persistenceManager->getObjectByIdentifier($this->properties['image'], 'TYPO3\TYPO3\Domain\Model\Media\Image');
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