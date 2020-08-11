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
 * Abstract implementation of a collection renderer for Fusion.
 * @deprecated since Neos 4.2 in favor of MapImplementation
 */
abstract class AbstractCollectionImplementation extends MapImplementation
{
    /**
     * Render the array collection by triggering the itemRenderer for every element
     *
     * @return array
     */
    public function getCollection()
    {
        return $this->fusionValue('collection');
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->getCollection();
    }

    /**
     * Evaluate the collection nodes as concatenated string
     *
     * @return string
     * @throws FusionException
     */
    public function evaluate()
    {
        return implode('', parent::evaluate());
    }

    /**
     * Evaluate the collection nodes as array
     *
     * @return array
     * @throws FusionException
     */
    public function evaluateAsArray()
    {
        return parent::evaluate();
    }
}
