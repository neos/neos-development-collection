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
 * The old "COA" object
 * @deprecated since Neos 4.2 in favor of JoinImplementation
 */
class ArrayImplementation extends JoinImplementation
{
    /**
     * Arrays are always concatenated with an empty string
     *
     * @return string
     */
    public function getGlue()
    {
        return '';
    }
}
