<?php
namespace TYPO3\Neos\TypoScript;

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
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Neos\Domain\Model\PluginViewDefinition;
use TYPO3\Neos\Service\PluginService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A TypoScript PluginView.
 */
class PluginViewImplementation extends PluginImplementation {

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

	/**
	 * @var PluginService
	 * @Flow\Inject
	 */
	protected $pluginService;

	/**
	 * Build the proper pluginRequest to render the PluginView
	 * of some configured Master Plugin
	 *
	 * @return ActionRequest
	 */
	protected function buildPluginRequest() {
		/** @var $parentRequest ActionRequest */
		$parentRequest = $this->tsRuntime->getControllerContext()->getRequest();
		$pluginRequest = new ActionRequest($parentRequest);

		if (!$this->node instanceof NodeInterface) {
			$pluginRequest->setArgumentNamespace('--' . $this->getPluginNamespace());
			$this->passArgumentsToPluginRequest($pluginRequest);
			$pluginRequest->setControllerPackageKey($this->getPackage());
			$pluginRequest->setControllerSubpackageKey($this->getSubpackage());
			$pluginRequest->setControllerName($this->getController());
			$pluginRequest->setControllerActionName($this->getAction());
			return $pluginRequest;
		}

		$pluginNodePath = $this->node->getProperty('plugin');
		if (strlen($pluginNodePath) === 0) {
			return $pluginRequest;
		}
		$pluginViewName = $this->node->getProperty('view');

		// Set the node to render this to the masterPlugin node
		$this->node = $this->propertyMapper->convert($pluginNodePath, 'TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$pluginRequest->setArgument('__node', $this->node);
		$pluginRequest->setArgumentNamespace('--' . $this->getPluginNamespace());
		$this->passArgumentsToPluginRequest($pluginRequest);

		if ($pluginRequest->getControllerObjectName() !== '') {
			return $pluginRequest;
		}

		$controllerObjectPairs = array();
		foreach ($this->pluginService->getPluginViewDefinitionsByPluginNodeType($this->node->getNodeType()) as $pluginViewDefinition) {

			/** @var PluginViewDefinition $pluginViewDefinition */
			if ($pluginViewDefinition->getName() !== $pluginViewName) {
				continue;
			}
			$controllerObjectPairs = $pluginViewDefinition->getControllerActionPairs();
			break;
		}

		if ($controllerObjectPairs === array()) {
			return $pluginRequest;
		}

		$defaultControllerObjectName = key($controllerObjectPairs);
		$defaultActionName = current($controllerObjectPairs[$defaultControllerObjectName]);
		$pluginRequest->setControllerObjectName($defaultControllerObjectName);
		$pluginRequest->setControllerActionName($defaultActionName);

		return $pluginRequest;
	}

	/**
	 * Returns the rendered content of this plugin
	 *
	 * @return string The rendered content as a string
	 * @throws StopActionException
	 */
	public function evaluate() {
		$currentContext = $this->tsRuntime->getCurrentContext();
		$this->node = $currentContext['node'];
		/** @var $parentResponse Response */
		$parentResponse = $this->tsRuntime->getControllerContext()->getResponse();
		$pluginResponse = new Response($parentResponse);

		$pluginRequest = $this->buildPluginRequest();
		if ($pluginRequest->getControllerObjectName() === '') {
			$message = 'Master View not selected';
			if ($this->node->getProperty('plugin')) {
				$message = 'Plugin View not selected';
			}
			if ($this->node->getProperty('view')) {
				$message ='Master View or Plugin View not found';
			}
			return $this->pluginViewNode->getContext()->getWorkspaceName() !== 'live' || $this->objectManager->getContext()->isDevelopment() ? '<p>' . $message . '</p>' : '<!-- ' . $message . '-->';
		}
		$this->dispatcher->dispatch($pluginRequest, $pluginResponse);
		return $pluginResponse->getContent();
	}
}
