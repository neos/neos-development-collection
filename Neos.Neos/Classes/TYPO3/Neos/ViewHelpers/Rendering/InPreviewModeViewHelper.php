<?php
namespace Neos\Neos\ViewHelpers\Rendering;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

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
class InPreviewModeViewHelper extends AbstractRenderingStateViewHelper
{
    /**
     * @param NodeInterface $node Optional Node to use context from
     * @param string $mode Optional rendering mode name to check if this specific mode is active
     * @return boolean
     * @throws \Neos\Neos\Exception
     */
    public function render(NodeInterface $node = null, $mode = null)
    {
        $context = $this->getNodeContext($node);
        $renderingMode = $context->getCurrentRenderingMode();
        if ($mode !== null) {
            $result = ($renderingMode->getName() === $mode) && $renderingMode->isPreview();
        } else {
            $result = $renderingMode->isPreview();
        }

        return $result;
    }
}
