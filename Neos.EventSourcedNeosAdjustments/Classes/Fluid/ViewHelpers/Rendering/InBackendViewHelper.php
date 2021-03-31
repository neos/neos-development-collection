<?php
namespace Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Rendering;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;

/**
 * ViewHelper to find out if Neos is rendering the backend.
 *
 * = Examples =
 *
 * Given we are currently seeing the Neos backend:
 *
 * <code title="Basic usage">
 * <f:if condition="{neos:rendering.inBackend()}">
 *   <f:then>
 *     Shown in the backend.
 *   </f:then>
 *   <f:else>
 *     Shown when not in backend.
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown in the backend.
 * </output>
 */
class InBackendViewHelper extends AbstractRenderingStateViewHelper
{
    /**
     * @param NodeBasedReadModelInterface $node
     * @return boolean
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function render(NodeBasedReadModelInterface $node = null)
    {
        return (!$node->getAddress()->isInLiveWorkspace() && $this->hasAccessToBackend());
    }
}
