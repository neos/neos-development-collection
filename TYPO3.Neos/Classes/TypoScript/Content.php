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
 * The TypoScript "Content" object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Content extends \F3\TypoScript\AbstractContentObject implements \ArrayAccess {

	/**
	 * @var array
	 */
	protected $sections;

	/**
	 * Returns the section with the specified name or NULL if no such section
	 * exists
	 *
	 * @param string $offset Name of the section to return
	 * @return mixed 
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetGet($offset) {
		if ($this->sections === NULL) {
			$this->initializeSections();
		}
		return (isset($this->sections[$offset])) ? $this->sections[$offset] : NULL;
	}

	/**
	 * Tells if a section with the given name exists.
	 *
	 * @return boolean TRUE if the section exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetExists($offset) {
		if ($this->sections === NULL) {
			$this->initializeSections();
		}
		return isset($this->sections[$offset]);
	}

	/**
	 * Sets the specified section
	 *
	 * @param string $offset The offset (name of the sectino) of the value to set.
	 * @param \F3\TypoScript\ContentObjectInterface $value The value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetSet($offset, $value) {
		if ($this->sections === NULL) {
			$this->initializeSections();
		}
		if (!$value instanceof \F3\TypoScript\ContentObjectInterface) {
			throw new \InvalidArgumentException('A section must be a valid TypoScript content object.', 1273764535);
		}
		$this->sections[$offset] = $value;
	}

	/**
	 * Unsets the specified section
	 *
	 * @param string $offset The offset (section name) of the value to unset.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetUnset($offset) {
		if ($this->sections === NULL) {
			$this->initializeSections();
		}
		unset($this->sections[$offset]);
	}

	/**
	 * Initializes the internal sections array.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeSections() {
		$this->sections = array();

		$contentContext = $this->renderingContext->getContentContext();

		$sectionNodes = $contentContext->getCurrentNode()->getChildNodes('typo3:section');
		foreach ($sectionNodes as $sectionNode) {
			$contentArray = $this->typoScriptObjectFactory->createByName('ContentArray');
			$i = 0;

			foreach ($sectionNode->getChildNodes() as $sectionChildNode) {
				if ($sectionChildNode->getContentType() !== 'typo3:page') {
					$typoScriptObject = $this->typoScriptObjectFactory->createByNode($sectionChildNode);
					$contentArray[$i] = $typoScriptObject;
					$i++;
				}
			}

			if ($i > 0) {
				$this->sections[$sectionNode->getName()] = $contentArray;
			}
		}
	}
}
?>