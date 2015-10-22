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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

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
     * Add the current node identifier to be used for cache entry tagging
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
        list($nodePath, $contextArguments) = explode('@', $values['node']);
        $context = $this->getContext($contextArguments);
        $node = $context->getNode($nodePath);
        if ($node instanceof NodeInterface) {
            $values['node-identifier'] = $node->getIdentifier();
            $joinPoint->setMethodArgument('values', $values);
        }
    }

    /**
     * Create a context object based on the context stored in the node path
     *
     * @param string $contextArguments
     * @return Context
     */
    protected function getContext($contextArguments)
    {
        $contextConfiguration = explode(';', $contextArguments);
        $workspaceName = array_shift($contextConfiguration);
        $dimensionConfiguration = explode('&', array_shift($contextConfiguration));

        $dimensions = array();
        foreach ($contextConfiguration as $dimension) {
            list($dimensionName, $dimensionValue) = explode('=', $dimension);
            $dimensions[$dimensionName] = explode(',', $dimensionValue);
        }

        $context = $this->contextFactory->create(array(
            'workspaceName' => $workspaceName,
            'dimensions' => $dimensions,
            'invisibleContentShown' => true
        ));

        return $context;
    }
}
