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
 * attributes.class = Neos.Fusion:RawArray {
 *  class1: 'class1'
 *  class2: 'class2'
 * }
 * attributes.id = 'my-id'
 *
 * will result in the string: class="class1 class2" id="my-id"
 */
class AttributesImplementation extends AbstractArrayFusionObject
{
    /**
     * @return string
     */
    public function evaluate()
    {
        $allowEmpty = $this->getAllowEmpty();
        $attributes = [];
        foreach (array_keys($this->properties) as $attributeName) {
            if ($attributeName === '__meta' || in_array($attributeName, $this->ignoreProperties)) {
                continue;
            }
            $attributes[$attributeName] = $this->fusionValue($attributeName);
        }
        return TagImplementation::renderAttributes($attributes, $allowEmpty);
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
