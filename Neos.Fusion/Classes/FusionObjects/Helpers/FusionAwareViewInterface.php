<?php
namespace Neos\Fusion\FusionObjects\Helpers;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * You should implement this interface with a View that should allow access
 * to the Fusion object it is rendered from (and so the Fusion runtime).
 *
 * The Fusion FluidView is the reference implementation for this.
 * @see \Neos\Fusion\FusionObjects\Helpers\FluidView
 *
 * @api
 */
interface FusionAwareViewInterface
{
    /**
     * @return AbstractFusionObject
     */
    public function getFusionObject();
}
