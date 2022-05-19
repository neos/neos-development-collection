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

use Neos\Fusion\Exception as FusionException;

/**
 * Fusion object to render and array of key value pairs by evaluating all properties
 */
class DataStructureImplementation extends AbstractArrayFusionObject
{
    /**
     * Evaluate this Fusion object and return the result
     *
     * @return array
     * @throws FusionException
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationException
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Security\Exception
     */
    public function evaluate()
    {
        return $this->evaluateNestedProperties('Neos.Fusion:DataStructure');
    }

    /**
     * Sort the Fusion objects inside $this->properties depending on:
     * - numerical ordering
     * - position meta-property
     *
     * This will ignore all properties defined in "@ignoreProperties" in Fusion
     *
     * @return array an ordered list of key value pairs
     * @throws FusionException if the positional string has an unsupported format
     * @see PositionalArraySorter
     *
     * @deprecated since 8.0 can be removed with 9.0 use {@see \Neos\Fusion\FusionObjects\AbstractArrayFusionObject::preparePropertyKeys}
     */
    protected function sortNestedFusionKeys()
    {
        return $this->preparePropertyKeys($this->properties);
    }
}
