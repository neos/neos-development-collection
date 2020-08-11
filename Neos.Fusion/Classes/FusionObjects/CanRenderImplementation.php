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
 * CanRender Fusion-Object
 *
 * The CanRender Fusion object checks whether the given type can be rendered
 */
class CanRenderImplementation extends AbstractFusionObject
{
    /**
     * Fusion type which shall be rendered
     *
     * @return string
     */
    public function getType()
    {
        return $this->fusionValue('type');
    }

    /**
     * @return boolean
     */
    public function evaluate()
    {
        return $this->runtime->canRender('/type<' . $this->getType() . '>');
    }
}
