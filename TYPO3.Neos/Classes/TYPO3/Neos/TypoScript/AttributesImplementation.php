<?php
namespace TYPO3\Neos\TypoScript;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * Renders a string of xml attributes from the properties of this TypoScript object.
 * So a configuration like:
 *
 * attributes = TYPO3.Neos:Attributes
 * attributes.class = 'my-class'
 * attributes.id = 'my-id'
 *
 * will result in the string: class="my-class" id="my-id"
 */
class AttributesImplementation extends AbstractTypoScriptObject {

	/**
	 * @return string
	 */
	public function evaluate() {
		$renderedAttributes = '';
		foreach (array_keys($this->properties) as $attributeName) {
			$encodedAttributeName = htmlspecialchars($attributeName, ENT_COMPAT, 'UTF-8', FALSE);
			$attributeValue = $this->tsValue($attributeName);
			if ($attributeValue === NULL) {
				$renderedAttributes .= ' ' . $encodedAttributeName;
			} else {
				$encodedAttributeValue = htmlspecialchars($attributeValue, ENT_COMPAT, 'UTF-8', FALSE);
				$renderedAttributes .= ' ' . $encodedAttributeName . '="' . $encodedAttributeValue . '"';
			}
		}
		return $renderedAttributes;
	}
}
