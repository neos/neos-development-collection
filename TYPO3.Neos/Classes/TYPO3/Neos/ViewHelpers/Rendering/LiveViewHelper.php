<?php
namespace TYPO3\Neos\ViewHelpers\Rendering;

/*
 * This file is part of the TYPO3.Neos package.
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
 * ViewHelper to find out if Neos is rendering the live website.
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
class LiveViewHelper extends AbstractRenderingStateViewHelper
{
    /**
     * @param NodeInterface $node
     * @return boolean
     */
    public function render(NodeInterface $node = null)
    {
        $context = $this->getNodeContext($node);

        return $context->isLive();
    }
}
