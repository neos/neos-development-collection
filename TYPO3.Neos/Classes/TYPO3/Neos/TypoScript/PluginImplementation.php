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

/**
 * A TypoScript Plugin object. TODO REFACTOR!!
 *
 * @Flow\Scope("prototype")
 */
class PluginImplementation extends \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * @var string
	 */
	protected $package = NULL;

	/**
	 * @var string
	 */
	protected $subpackage = NULL;

	/**
	 * @var string
	 */
	protected $controller = NULL;

	/**
	 * @var string
	 */
	protected $action = NULL;

	/**
	 * @var string
	 */
	protected $argumentNamespace = NULL;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $node;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $documentNode;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @param string $package
	 * @return void
	 */
	public function setPackage($package) {
		$this->package = $package;
	}

	/**
	 * @return string
	 */
	public function getPackage() {
		return $this->package;
	}

	/**
	 * @param string $subpackage
	 * @return void
	 */
	public function setSubpackage($subpackage) {
		$this->subpackage = $subpackage;
	}

	/**
	 * @return string
	 */
	public function getSubpackage() {
		return $this->subpackage;
	}

	/**
	 * @param string $controller
	 * @return void
	 */
	public function setController($controller) {
		$this->controller = $controller;
	}

	/**
	 * @return string
	 */
	public function getController() {
		return $this->controller;
	}

	/**
	 * @param string $action
	 * @return void
	 */
	public function setAction($action) {
		$this->action = $action;
	}

	/**
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @param string $argumentNamespace
	 * @return void
	 */
	public function setArgumentNamespace($argumentNamespace) {
		$this->argumentNamespace = $argumentNamespace;
	}

	/**
	 * @return string
	 */
	public function getArgumentNamespace() {
		return $this->argumentNamespace;
	}

	/**
	 * Build the pluginRequest object
	 *
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected function buildPluginRequest() {
		$parentRequest = $this->tsRuntime->getControllerContext()->getRequest();
		$pluginRequest = new ActionRequest($parentRequest);
		$pluginRequest->setArgumentNamespace('--' . $this->getPluginNamespace());
		$this->passArgumentsToPluginRequest($pluginRequest);

		if ($this->node instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) {
			if ($pluginRequest->getControllerPackageKey() === NULL) {
				$pluginRequest->setControllerPackageKey($this->node->getProperty('package') ?: $this->package);
			}
			if ($pluginRequest->getControllerSubpackageKey() === NULL) {
				$pluginRequest->setControllerSubpackageKey($this->node->getProperty('subpackage') ?: $this->subpackage);
			}
			if ($pluginRequest->getControllerName() === NULL) {
				$pluginRequest->setControllerName($this->node->getProperty('controller') ?: $this->controller);
			}
			if ($this->action === NULL) {
				$this->action = 'index';
			}
			if ($pluginRequest->getControllerActionName() === NULL) {
				$pluginRequest->setControllerActionName($this->node->getProperty('action') ?: $this->action);
			}

			foreach ($this->properties as $key => $value) {
				$evaluatedValue = $this->tsRuntime->evaluateProcessor($key, $this, $value);
				$pluginRequest->setArgument('__' . $key, $evaluatedValue);
			}

			$pluginRequest->setArgument('__node', $this->node);
			$pluginRequest->setArgument('__documentNode', $this->documentNode);
		} else {
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
		try {
			$currentContext = $this->tsRuntime->getCurrentContext();
			$this->node = $currentContext['node'];
			$this->documentNode = $currentContext['documentNode'];
			$parentResponse = $this->tsRuntime->getControllerContext()->getResponse();
			$pluginResponse = new Response($parentResponse);

			$this->dispatcher->dispatch($this->buildPluginRequest(), $pluginResponse);
			$content = $pluginResponse->getContent();
		} catch (\Exception $exception) {
			$content = $this->tsRuntime->handleRenderingException($this->path, $exception);
		}
		if ($this->node instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) {
			return $this->contentElementWrappingService->wrapContentObject($this->node, $this->path, $content);
		} else {
			return $content;
		}
	}

	/**
	 * Returns the plugin namespace that will be prefixed to plugin parameters in URIs.
	 * By default this is <plugin_class_name>
	 *
	 * @return string
	 * @todo make this configurable
	 */
	protected function getPluginNamespace() {
		if ($this->node instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) {
			$nodeArgumentNamespace = $this->node->getProperty('argumentNamespace');
			if ($nodeArgumentNamespace !== NULL) {
				return $nodeArgumentNamespace;
			}
		}

		if ($this->argumentNamespace !== NULL) {
			return $this->argumentNamespace;
		}

		return strtolower(str_replace('\\', '_', get_class($this)));
	}

	/**
	 * Pass the arguments which were addressed to the plugin to its own request
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $pluginRequest The plugin request
	 * @return void
	 */
	protected function passArgumentsToPluginRequest(ActionRequest $pluginRequest) {
		$arguments = $pluginRequest->getMainRequest()->getPluginArguments();
		$pluginNamespace = $this->getPluginNamespace();

		if (isset($arguments[$pluginNamespace])) {
			$pluginRequest->setArguments($arguments[$pluginNamespace]);
		}
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->evaluate();
	}
}
?>
