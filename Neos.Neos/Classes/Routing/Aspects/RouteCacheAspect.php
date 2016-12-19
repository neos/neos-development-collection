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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Context;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Utility\NodePaths;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class RouteCacheAspect
{

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

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
        if (!isset($values['node']) || strpos($values['node'], '@') === false) {
            return;
        }

        // Build context explicitly without authorization checks because the security context isn't available yet
        // anyway and any Entity Privilege targeted on Workspace would fail at this point:
        $this->securityContext->withoutAuthorizationChecks(function () use ($joinPoint, $values) {
            $contextPathPieces = NodePaths::explodeContextPath($values['node']);
            $context = $this->contextFactory->create([
                'workspaceName' => $contextPathPieces['workspaceName'],
                'dimensions' => $contextPathPieces['dimensions'],
                'invisibleContentShown' => true
            ]);

            $node = $context->getNode($contextPathPieces['nodePath']);
            if (!$node instanceof NodeInterface) {
                return;
            }

            $values['node-identifier'] = $node->getIdentifier();
            $node = $node->getParent();

            $values['node-parent-identifier'] = array();
            while ($node !== null) {
                $values['node-parent-identifier'][] = $node->getIdentifier();
                $node = $node->getParent();
            }

            $joinPoint->setMethodArgument('values', $values);
        });
    }
}
