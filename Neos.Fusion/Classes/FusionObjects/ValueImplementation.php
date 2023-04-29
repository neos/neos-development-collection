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
 * Value object for simple type handling as Fusion objects
 *
 * //fusionPath value The value this object should be evaluated to
 * @api
 */
class ValueImplementation extends AbstractFusionObject
{
    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->fusionValue('value');
    }

    /**
     * Just return the processed value
     *
     * @return mixed
     */
    public function evaluate()
    {
        return $this->getValue();
    }
}
