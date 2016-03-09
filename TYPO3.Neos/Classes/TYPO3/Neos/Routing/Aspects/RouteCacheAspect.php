<?php
namespace TYPO3\Neos\Routing\Aspects;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

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
     * @Flow\Before("method(TYPO3\Flow\Mvc\Routing\RouterCachingService->extractUuids())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
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
