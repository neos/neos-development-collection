<?php
namespace Neos\Neos\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A Fusion RawDimensionMenu object
 */
class RawDimensionsMenuImplementation extends DimensionsMenuImplementation
{

    /**
     * @return array
     */
    public function evaluate()
    {
        return $this->getItems();
    }
}
