<?php
namespace TYPO3\Neos\TypoScript;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Neos.NodeTypes".        *
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
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected function buildPluginRequest() {
		$parentRequest = $this->tsRuntime->getControllerContext()->getRequest();
		$pluginRequest = new ActionRequest($parentRequest);

		if ($this->node instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) {
			$pluginNodePath = $this->node->getProperty('plugin');
			$pluginViewName = $this->node->getProperty('view');

			// Set the node to render this to the masterPlugin node
			if (strlen($pluginNodePath) > 0) {
				$this->node = $this->propertyMapper->convert($pluginNodePath, 'TYPO3\TYPO3CR\Domain\Model\NodeInterface');
				$pluginRequest->setArgumentNamespace('--' . $this->getPluginNamespace());
				$this->passArgumentsToPluginRequest($pluginRequest);

				if ($this->node instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) {
					$controllerObjectPairs = array();
					foreach ($this->pluginService->getPluginViewDefinitionsByPluginNodeType($this->node->getNodeType()) as $pluginViewDefinition) {
						/** @var PluginViewDefinition $pluginViewDefinition */
						if ($pluginViewDefinition->getName() !== $pluginViewName) {
							continue;
						}
						$controllerObjectPairs = $pluginViewDefinition->getControllerActionPairs();
						break;
					}
					if ($controllerObjectPairs !== array()) {
						$controllerObjectName = key($controllerObjectPairs);
						$action = current($controllerObjectPairs[$controllerObjectName]);

						$pluginRequest->setControllerObjectName($controllerObjectName);
						$pluginRequest->setControllerActionName($action);
						$pluginRequest->setArgument('__node', $this->node);
					}
				}
			}
		} else {
			$pluginRequest->setArgumentNamespace('--' . $this->getPluginNamespace());
			$this->passArgumentsToPluginRequest($pluginRequest);
			$pluginRequest->setControllerPackageKey($this->getPackage());
			$pluginRequest->setControllerSubpackageKey($this->getSubpackage());
			$pluginRequest->setControllerName($this->getController());
			$pluginRequest->setControllerActionName($this->getAction());
		}
		return $pluginRequest;
	}

	/**
	 * Returns the rendered content of this plugin
	 *
	 * @return string The rendered content as a string
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 */
	public function evaluate() {

		$currentContext = $this->tsRuntime->getCurrentContext();
		$this->node = $currentContext['node'];
		$parentResponse = $this->tsRuntime->getControllerContext()->getResponse();
		$pluginResponse = new Response($parentResponse);

		try {
			$pluginRequest = $this->buildPluginRequest();
			if ($pluginRequest->getControllerObjectName() === '') {
				$content = 'No PluginView Configured';
				return $this->contentElementWrappingService->wrapContentObject($currentContext['node'], $this->path, $content);
			}
			$this->dispatcher->dispatch($pluginRequest, $pluginResponse);

			if ($this->node instanceof NodeInterface) {
				return $this->contentElementWrappingService->wrapContentObject($currentContext['node'], $this->path, $pluginResponse->getContent());
			} else {
				return $pluginResponse->getContent();
			}
		} catch (\TYPO3\Flow\Mvc\Exception\StopActionException $stopActionException) {
			throw $stopActionException;
		} catch (\TYPO3\Flow\Mvc\Exception\RequiredArgumentMissingException $exception) {
			$content = $exception->getMessage();
			return $this->contentElementWrappingService->wrapContentObject($currentContext['node'], $this->path, $content);
		} catch (\Exception $exception) {
			$this->systemLogger->logException($exception);
			$message = 'Exception #' . $exception->getCode() . ' thrown while rendering ' . get_class($this) . '. See log for more details.';

			$content = ($this->objectManager->getContext()->isDevelopment()) ? ('<strong>' . $message . '</strong>') : ('<!--' . $message . '-->');
			return $this->contentElementWrappingService->wrapContentObject($currentContext['node'], $this->path, $content);
		}
	}
}
?>