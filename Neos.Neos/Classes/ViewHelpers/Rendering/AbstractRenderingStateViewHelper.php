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
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\FusionObjects\Helpers\TypoScriptAwareViewInterface;

/**
 * Abstract ViewHelper for all Neos rendering state helpers.
 */
abstract class AbstractRenderingStateViewHelper extends AbstractViewHelper
{
    /**
     * Get a node from the current TypoScript context if available.
     *
     * @return NodeInterface|NULL
     *
     * @TODO Refactor to a TypoScript Context trait (in Neos.Fusion) that can be used inside ViewHelpers to get variables from the TypoScript context.
     */
    protected function getContextNode()
    {
        $baseNode = null;
        $view = $this->viewHelperVariableContainer->getView();
        if ($view instanceof TypoScriptAwareViewInterface) {
            $typoScriptObject = $view->getTypoScriptObject();
            $currentContext = $typoScriptObject->getRuntime()->getCurrentContext();
            if (isset($currentContext['node'])) {
                $baseNode = $currentContext['node'];
            }
        }

        return $baseNode;
    }

    /**
     * @param NodeInterface $node
     * @return ContentContext
     * @throws ViewHelperException
     */
    protected function getNodeContext(NodeInterface $node = null)
    {
        if ($node === null) {
            $node = $this->getContextNode();
            if ($node === null) {
                throw new ViewHelperException('The ' . get_class($this) . ' needs a Node to determine the state. We could not find one in your context so please provide it as "node" argument to the ViewHelper.', 1427267133);
            }
        }

        $context = $node->getContext();
        if (!$context instanceof ContentContext) {
            throw new ViewHelperException('Rendering state can only be obtained with Nodes that are in a Neos ContentContext. Please provide a Node with such a context.', 1427720037);
        }

        return $context;
    }
}
