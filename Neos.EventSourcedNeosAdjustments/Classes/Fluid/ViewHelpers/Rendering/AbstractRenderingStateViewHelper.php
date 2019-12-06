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

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\Flow\Security\Authorization\PrivilegeManager;
use Neos\Flow\Security\Exception;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;
use Neos\Fusion\FusionObjects\Helpers\FusionAwareViewInterface;

/**
 * Abstract ViewHelper for all Neos rendering state helpers.
 */
abstract class AbstractRenderingStateViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var PrivilegeManager
     */
    protected $privilegeManager;


    /**
     * Get a node from the current Fusion context if available.
     *
     * @param TraversableNodeInterface|null $node
     * @return NodeAddress
     *
     * @throws ViewHelperException
     * @TODO Refactor to a Fusion Context trait (in Neos.Fusion) that can be used inside ViewHelpers to get variables from the Fusion context.
     */
    protected function getNodeAddressOfContextNode(?TraversableNodeInterface $node): NodeAddress
    {
        if ($node !== null) {
            return $this->nodeAddressFactory->createFromTraversableNode($node);
        }

        $baseNode = null;
        $view = $this->viewHelperVariableContainer->getView();
        if ($view instanceof FusionAwareViewInterface) {
            $fusionObject = $view->getFusionObject();
            $currentContext = $fusionObject->getRuntime()->getCurrentContext();
            if (isset($currentContext['node'])) {
                $baseNode = $currentContext['node'];
            }
        }

        if ($baseNode === null) {
            throw new ViewHelperException('The ' . get_class($this) . ' needs a Node to determine the state. We could not find one in your context so please provide it as "node" argument to the ViewHelper.', 1427267133);
        }

        return $this->nodeAddressFactory->createFromTraversableNode($baseNode);
    }

    protected function hasAccessToBackend(): bool
    {
        try {
            return $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess');
        } catch (Exception $exception) {
            return false;
        }
    }
}
