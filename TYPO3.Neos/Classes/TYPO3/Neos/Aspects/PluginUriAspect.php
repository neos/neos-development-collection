<?php
namespace TYPO3\Neos\Aspects;

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
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Eel\FlowQuery\FlowQuery;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class PluginUriAspect {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * The pluginService
	 *
	 * @var \TYPO3\Neos\Service\PluginService
	 * @Flow\Inject
	 */
	protected $pluginService;

	/**
	 * @Flow\Around("method(TYPO3\Flow\Mvc\Routing\UriBuilder->uriFor())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return string The result of the target method if it has not been intercepted
	 */
	public function rewritePluginViewUris(JoinPointInterface $joinPoint) {
		$request = $joinPoint->getProxy()->getRequest();
		$arguments = $joinPoint->getMethodArguments();

		$currentNode = $request->getInternalArgument('__node');
		if (!$request->getMainRequest()->hasArgument('node') || !$currentNode instanceof Node) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$currentNode = $request->getInternalArgument('__node');
		$controllerObjectName = $this->getControllerObjectName($request, $arguments);
		$actionName = $arguments['actionName'] !== NULL ? $arguments['actionName'] : $request->getControllerActionName();

		$targetNode = $this->pluginService->getPluginNodeByAction($currentNode, $controllerObjectName, $actionName);

		// TODO override namespace

		$q = new FlowQuery(array($targetNode));
		$pageNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
		$result = $this->generateUriForNode($request, $joinPoint, $pageNode);

		return $result;
	}

	/**
	 * Merge the default plugin arguments of the Plugin with the arguments in the request
	 * and generate a controllerObjectName
	 *
	 * @param object $request
	 * @param array $arguments
	 * @return string $controllerObjectName
	 */
	public function getControllerObjectName($request, array $arguments) {
		$controllerName = $arguments['controllerName'] !== NULL ? $arguments['controllerName'] : $request->getControllerName();
		$subPackageKey = $arguments['subPackageKey'] !== NULL ? $arguments['subPackageKey'] : $request->getControllerSubpackageKey();
		$packageKey = $arguments['packageKey'] !== NULL ? $arguments['packageKey'] : $request->getControllerPackageKey();

		$possibleObjectName = '@package\@subpackage\Controller\@controllerController';
		$possibleObjectName = str_replace('@package', str_replace('.', '\\', $packageKey), $possibleObjectName);
		$possibleObjectName = str_replace('@subpackage', $subPackageKey, $possibleObjectName);
		$possibleObjectName = str_replace('@controller', $controllerName, $possibleObjectName);
		$possibleObjectName = str_replace('\\\\', '\\', $possibleObjectName);

		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleObjectName);
		return ($controllerObjectName !== FALSE) ? $controllerObjectName : '';
	}

	/**
	 * This method generates the Uri through the joinPoint with
	 * temporary overriding the used node
	 *
	 * @param ActionRequest $request
	 * @param JoinPointInterface $joinPoint The current join point
	 * @param NodeInterface $node
	 * @return string $uri
	 */
	public function generateUriForNode(ActionRequest $request, JoinPointInterface $joinPoint, NodeInterface $node) {
		// store original node path to restore it after generating the uri
		$originalNodePath = $request->getMainRequest()->getArgument('node');

		// generate the uri for the given node
		$request->getMainRequest()->setArgument('node', $node->getContextPath());
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);

		// restore the original node path
		$request->getMainRequest()->setArgument('node', $originalNodePath);

		return $result;
	}
}
