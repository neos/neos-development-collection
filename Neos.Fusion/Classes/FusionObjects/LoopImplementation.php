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
 * Render a Fusion collection of using the itemRenderer
 *
 * //fusionPath items *Collection
 * //fusionPath itemRenderer the Fusion object which is triggered for each element in the node collection
 */
class LoopImplementation extends MapImplementation
{

    /**
     * Get the glue to insert between items
     *
     * @return string
     */
    public function getGlue()
    {
        return $this->fusionValue('__meta/glue') ?? '';
    }

    /**
     * Evaluate the collection nodes
     *
     * @return string
     */
    public function evaluate()
    {
        $glue = $this->getGlue();
        return implode($glue, parent::evaluate());
    }
}
