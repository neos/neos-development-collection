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
 * Render a Fusion collection of nodes
 *
 * //fusionPath collection *Collection
 * //fusionPath itemRenderer the Fusion object which is triggered for each element in the node collection
 * @deprecated since Neos 4.2 in favor of LoopImplementation
 */
class CollectionImplementation extends AbstractCollectionImplementation
{
    /**
     * Collections are always concatenated with an empty string
     *
     * @return string
     */
    public function getGlue()
    {
        return '';
    }

    /**
     * Evaluate the collection nodes
     *
     * @return string
     */
    public function evaluate()
    {
        return parent::evaluate();
    }
}
