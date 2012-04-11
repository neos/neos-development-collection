<?php
namespace TYPO3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Mvc\ActionRequest;
use TYPO3\FLOW3\Http\Response;

/**
 * A TypoScript Plugin object. TODO REFACTOR!!
 *
 * @FLOW3\Scope("prototype")
 */
class Plugin extends \TYPO3\TypoScript\TypoScriptObjects\AbstractTsObject {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Service\ContentElementWrappingService
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
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
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
	 * @return \TYPO3\FLOW3\Mvc\ActionRequest
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

			// TODO Check if we want to use all properties as arguments
			//      This enables us to configure plugin controller arguments via
			//      content type definitions for now.
			foreach ($this->node->getProperties() as $propertyName => $propertyValue) {
				$propertyName = '--' . $propertyName;
				if (!in_array($propertyName, array('--package', '--subpackage', '--controller', '--action', '--format')) && !$pluginRequest->hasArgument($propertyName)) {
					$pluginRequest->setArgument($propertyName, $propertyValue);
				}
			}
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
	 */
	public function evaluate($currentContext) {
		$this->node = $currentContext;
		$parentResponse = $this->tsRuntime->getControllerContext()->getResponse();
		$pluginResponse = new Response($parentResponse);

		try {
			$this->dispatcher->dispatch($this->buildPluginRequest(), $pluginResponse);

			if ($this->node instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) {
				return $this->contentElementWrappingService->wrapContentObject($this->node, $pluginResponse->getContent());
			} else {
				return $pluginResponse->getContent();
			}
		} catch (\TYPO3\FLOW3\Mvc\Exception\StopActionException $stopActionException) {
			throw $stopActionException;
		} catch (\Exception $exception) {
			$this->systemLogger->logException($exception);
			$message = 'Exception #' . $exception->getCode() . ' thrown while rendering ' . get_class($this) . '. See log for more details.';
			return ($this->objectManager->getContext() === 'Development') ? ('<strong>' . $message . '</strong>') : ('<!--' . $message . '-->');
		}
	}

	/**
	 * Returns the plugin namespace that will be prefixed to plugin parameters in URIs.
	 * By default this is <plugin_class_name>
	 *
	 * @return void
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
	 * @param \TYPO3\FLOW3\Mvc\ActionRequest $pluginRequest The plugin request
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
		return $this->render();
	}
}
?>
