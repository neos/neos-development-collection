<?php
namespace Neos\Neos\Routing\Aspects;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Context;
use Neos\ContentRepository\Service\NodePaths;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class RouteCacheAspect
{
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    #[Flow\Inject]
    protected NodeAddressFactory $nodeAddressFactory;

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

    /**
     * Add the current node and all parent identifiers to be used for cache entry tagging
     *
     * @Flow\Before("method(Neos\Flow\Mvc\Routing\RouterCachingService->extractUuids())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function addCurrentNodeIdentifier(JoinPointInterface $joinPoint)
    {
        $values = $joinPoint->getMethodArgument('values');

        if (!isset($values['node'])) {
            return;
        }

        if (is_array($values['node']) && isset($values['node']['__contextNodePath'])) {
            $values['node'] = $values['node']['__contextNodePath'];
        }

        if (strpos($values['node'], '@') === false) {
            return;
        }

        // Build context explicitly without authorization checks because the security context isn't available yet
        // anyway and any Entity Privilege targeted on Workspace would fail at this point:
        $this->securityContext->withoutAuthorizationChecks(function () use ($joinPoint, $values) {
            $nodeAddress = $this->nodeAddressFactory->createFromContextPath($values['node']);
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $nodeAddress->contentStreamIdentifier,
                $nodeAddress->dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
            $node = $nodeAccessor->findByIdentifier($nodeAddress->nodeAggregateIdentifier);

            if (!$node instanceof NodeInterface) {
                return;
            }

            $values['node-identifier'] = (string)$node->getNodeAggregateIdentifier();
            $values['node-parent-identifier'] = [];
            $ancestor = $node;
            while ($ancestor = $nodeAccessor->findParentNode($ancestor)) {
                $values['node-parent-identifier'][] = (string)$ancestor->getNodeAggregateIdentifier();
            }
            $joinPoint->setMethodArgument('values', $values);
        });
    }

    /**
     * Add the current workspace name as a tag for the route cache entry
     *
     * @Flow\Around("method(Neos\Flow\Mvc\Routing\RouterCachingService->generateRouteTags())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return array<int,string>
     */
    public function addWorkspaceName(JoinPointInterface $joinPoint)
    {
        $tags = $joinPoint->getAdviceChain()->proceed($joinPoint);

        $values = $joinPoint->getMethodArgument('routeValues');

        if (!isset($values['node'])) {
            return $tags;
        }

        if (is_array($values['node']) && isset($values['node']['__contextNodePath'])) {
            $values['node'] = $values['node']['__contextNodePath'];
        }

        if (strpos($values['node'], '@') !== false) {
            // Build context explicitly without authorization checks because the security context isn't available yet
            // anyway and any Entity Privilege targeted on Workspace would fail at this point:
            $this->securityContext->withoutAuthorizationChecks(function () use ($values, &$tags) {
                $contextPathPieces = NodePaths::explodeContextPath($values['node']);
                $tags[] = $contextPathPieces['workspaceName'];
            });
        }
        return $tags;
    }
}
