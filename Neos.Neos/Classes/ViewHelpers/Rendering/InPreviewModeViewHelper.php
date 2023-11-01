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
 * ViewHelper to find out if Neos is rendering a preview mode.
 *
 * = Examples =
 *
 * Given we are currently in a preview mode:
 *
 * <code title="Basic usage">
 * <f:if condition="{neos:rendering.inPreviewMode()}">
 *   <f:then>
 *     Shown in preview.
 *   </f:then>
 *   <f:else>
 *     Shown elsewhere (edit mode or not in backend).
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown in preview.
 * </output>
 *
 *
 * Given we are in the preview mode named "desktop"
 *
 * <code title="Advanced usage">
 *
 * <f:if condition="{neos:rendering.inPreviewMode(mode: 'print')}">
 *   <f:then>
 *     Shown just for print preview mode.
 *   </f:then>
 *   <f:else>
 *     Shown in all other cases.
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown in all other cases.
 * </output>
 */
class InPreviewModeViewHelper extends AbstractViewHelper
{
    use FusionContextTrait;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument(
            'mode',
            'string',
            'Optional rendering mode name to check if this specific mode is active'
        );
    }

    public function render(Node $node = null, string $mode = null): bool
    {
        $renderingMode = $this->getContextVariable('renderingMode');
        if ($renderingMode instanceof RenderingMode) {
            $mode = $this->arguments['mode'];
            if ($mode) {
                return $renderingMode->isPreview && $renderingMode->name === $mode;
            }
            return $renderingMode->isPreview;
        }
        return false;
    }
}
