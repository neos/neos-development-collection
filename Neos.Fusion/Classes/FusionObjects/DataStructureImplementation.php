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

use Neos\Fusion\Core\Parser;

/**
 * Fusion object to render and array of key value pairs by evaluating all properties
 */
class DataStructureImplementation extends AbstractArrayFusionObject
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function evaluate()
    {
        return $this->evaluateNestedProperties('Neos.Fusion:DataStructure');
    }

    /**
     * Returns TRUE if the given property has no object type assigned
     *
     * @param mixed $property
     * @return bool
     * @deprecated since 8.0 can be renoved with 9.0 use \Neos\Fusion\FusionObjects\AbstractArrayFusionObject::sortNestedProperties
     */
    protected function sortNestedFusionKeys()
    {
        return parent::sortNestedProperties();
    }

    /**
     * Returns TRUE if the given property has no object type assigned
     *
     * @param mixed $property
     * @return bool
     * @deprecated since 8.0 can be renoved with 9.0 use \Neos\Fusion\FusionObjects\AbstractArrayFusionObject::isUntyped
     */
    private function isUntypedProperty($property): bool
    {
        if (!is_array($property)) {
            return false;
        }
        return array_intersect_key(array_flip(Parser::$reservedParseTreeKeys), $property) === [];
    }
}
