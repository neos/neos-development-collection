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
 * to the TypoScript object it is rendered from (and so the TypoScript runtime).
 *
 * The TypoScript FluidView is the reference implementation for this.
 * @see \Neos\Fusion\FusionObjects\Helpers\FluidView
 *
 * @api
 */
interface TypoScriptAwareViewInterface
{
    /**
     * @return AbstractFusionObject
     */
    public function getTypoScriptObject();
}
