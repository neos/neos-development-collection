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

namespace Neos\Neos\ViewHelpers\Rendering;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Fusion\ViewHelpers\FusionContextTrait;
use Neos\Neos\Domain\Model\RenderingMode;

/**
 * ViewHelper to find out if Neos is rendering the live website.
 * Make sure you either give a node from the current context to
 * the ViewHelper or have "node" set as template variable at least.
 *
 * = Examples =
 *
 * Given we are currently seeing the Neos backend:
 *
 * <code title="Basic usage">
 * <f:if condition="{neos:rendering.live()}">
 *   <f:then>
 *     Shown outside the backend.
 *   </f:then>
 *   <f:else>
 *     Shown in the backend.
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown in the backend.
 * </output>
 */
class LiveViewHelper extends AbstractViewHelper
{
    use FusionContextTrait;

    /**
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function render(): bool
    {
        $renderingMode = $this->getContextVariable('renderingMode');
        if ($renderingMode instanceof RenderingMode) {
            return $renderingMode->name === RenderingMode::FRONTEND;
        }
        return true;
    }
}
