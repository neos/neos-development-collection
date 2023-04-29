<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * Renders a string of xml attributes from the properties of this Fusion object.
 * So a configuration like:
 *
 * attributes = Neos.Fusion:Attributes
 * attributes.class = Neos.Fusion:DataStructure {
 *  class1: 'class1'
 *  class2: 'class2'
 * }
 * attributes.id = 'my-id'
 *
 * will result in the string: class="class1 class2" id="my-id"
 * @deprecated since Neos 5.0 in favor of TagImplementation or JoinImplementation
 */
class AttributesImplementation extends AbstractArrayFusionObject
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
            $attributeValue = $this->fusionValue($attributeName);
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
        $allowEmpty = $this->fusionValue('__meta/allowEmpty');
        if ($allowEmpty === null) {
            return true;
        } else {
            return (boolean)$allowEmpty;
        }
    }
}
