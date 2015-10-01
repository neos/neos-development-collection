<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Renders a string of xml attributes from the properties of this TypoScript object.
 * So a configuration like:
 *
 * attributes = TYPO3.TypoScript:Attributes
 * attributes.class = TYPO3.TypoScript:RawArray {
 *  class1: 'class1'
 *  class2: 'class2'
 * }
 * attributes.id = 'my-id'
 *
 * will result in the string: class="class1 class2" id="my-id"
 */
class AttributesImplementation extends AbstractArrayTypoScriptObject
{
    /**
     * @return string
     */
    public function evaluate()
    {
        $allowEmpty = $this->getAllowEmpty();

        $renderedAttributes = '';
        foreach (array_keys($this->properties) as $attributeName) {
            if ($attributeName === '__meta' || in_array($attributeName, $this->ignoreProperties)) {
                continue;
            }

            $encodedAttributeName = htmlspecialchars($attributeName, ENT_COMPAT, 'UTF-8', false);
            $attributeValue = $this->tsValue($attributeName);
            if ($attributeValue === null || $attributeValue === false) {
                // No op
            } elseif ($attributeValue === true || $attributeValue === '') {
                $renderedAttributes .= ' ' . $encodedAttributeName . ($allowEmpty ? '' : '=""');
            } else {
                if (is_array($attributeValue)) {
                    $joinedAttributeValue = '';
                    foreach ($attributeValue as $attributeValuePart) {
                        if ((string)$attributeValuePart !== '') {
                            $joinedAttributeValue .= ' ' . trim($attributeValuePart);
                        }
                    }
                    $attributeValue = trim($joinedAttributeValue);
                }
                $encodedAttributeValue = htmlspecialchars($attributeValue, ENT_COMPAT, 'UTF-8', false);
                $renderedAttributes .= ' ' . $encodedAttributeName . '="' . $encodedAttributeValue . '"';
            }
        }
        return $renderedAttributes;
    }

    /**
     * Whether empty attributes (HTML5 syntax) should be allowed
     *
     * @return boolean
     */
    protected function getAllowEmpty()
    {
        $allowEmpty = $this->tsValue('__meta/allowEmpty');
        if ($allowEmpty === null) {
            return true;
        } else {
            return (boolean)$allowEmpty;
        }
    }
}
