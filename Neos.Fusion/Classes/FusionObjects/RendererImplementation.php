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
 * Renderer Fusion Object
 *
 * The Renderer object will evaluate to a result using either ``renderer``,
 * ``renderPath`` or ``type`` from the configuration.
 */
class RendererImplementation extends AbstractFusionObject
{
    use RendererTrait;

    public function evaluate()
    {
        return $this->evaluateRenderer();
    }
}
