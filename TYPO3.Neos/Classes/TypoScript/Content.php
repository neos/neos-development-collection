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

/**
 * The TypoScript "Content" object
 *
 * @scope prototype
 */
class Content extends \TYPO3\TypoScript\AbstractContentObject implements \ArrayAccess {

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
	 * @param \TYPO3\TypoScript\ContentObjectInterface $value The value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetSet($offset, $value) {
		if ($this->sections === NULL) {
			$this->initializeSections();
		}
		if (!$value instanceof \TYPO3\TypoScript\ContentObjectInterface) {
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

		$sectionNodes = $contentContext->getCurrentNode()->getChildNodes('TYPO3.TYPO3:Section');
		foreach ($sectionNodes as $sectionNode) {
			$contentArray = $this->typoScriptObjectFactory->createByName('ContentArray');
			$contentArray->setNode($sectionNode);
			$i = 0;

			foreach ($sectionNode->getChildNodes() as $sectionChildNode) {
				if ($sectionChildNode->getContentType() !== 'TYPO3.TYPO3:Page') {
					$typoScriptObject = $this->typoScriptObjectFactory->createByNode($sectionChildNode);
					$contentArray[$i] = $typoScriptObject;
					$i++;
				}
			}

			$this->sections[$sectionNode->getName()] = $contentArray;
		}
	}
}
?>