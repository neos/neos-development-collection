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

use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
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
     * @var PrivilegeManager
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * Get a node from the current Fusion context if available.
     *
     * @param Node|null $node
     * @return \Neos\Neos\FrontendRouting\NodeAddress
     *
     * @throws ViewHelperException
     * @TODO Refactor to a Fusion Context trait (in Neos.Fusion) that can be used inside ViewHelpers
     * to get variables from the Fusion context.
     */
    protected function getNodeAddressOfContextNode(?Node $node): NodeAddress
    {
        if ($node !== null) {
            $contentRepository = $this->contentRepositoryRegistry->get(
                $node->subgraphIdentity->contentRepositoryIdentifier
            );
            return NodeAddressFactory::create($contentRepository)->createFromNode($node);
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
            throw new ViewHelperException(
                'The ' . get_class($this) . ' needs a Node to determine the state.'
                . ' We could not find one in your context so please provide it as "node" argument to the ViewHelper.',
                1427267133
            );
        }

        $contentRepository = $this->contentRepositoryRegistry->get(
            $baseNode->subgraphIdentity->contentRepositoryIdentifier
        );
        return NodeAddressFactory::create($contentRepository)->createFromNode($baseNode);
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
