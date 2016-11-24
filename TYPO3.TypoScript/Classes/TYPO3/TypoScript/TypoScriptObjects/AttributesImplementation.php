<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

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
