<?php
namespace TYPO3\Neos\Routing\Aspects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class RouteCacheAspect {

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
	public function addCurrentNodeIdentifier(JoinPointInterface $joinPoint) {
		$values = $joinPoint->getMethodArgument('values');
		if (!isset($values['node']) || strpos($values['node'], '@') === FALSE) {
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
	protected function getContext($contextArguments) {
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
			'invisibleContentShown' => TRUE
		));

		return $context;
	}

}
