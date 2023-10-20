<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\View;

/**
 * The interface for views capable of handling string serialized entry points
 */
interface RenderingEntryPointAware
{
    /**
     * Set the rendering entry point, e.g. a Fusion path to use for rendering
     */
    public function setRenderingEntryPoint(string $renderingEntryPoint): void;
}
