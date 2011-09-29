<?php
namespace TYPO3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A TypoScript Plugin object
 *
 * @scope prototype
 */
class Plugin extends \TYPO3\TypoScript\AbstractObject implements \TYPO3\TypoScript\ContentObjectInterface {

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\MVC\Web\SubRequestBuilder
	 */
	protected $subRequestBuilder;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\MVC\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @inject
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
	 * @inject
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * The rendering context as passed to render()
	 *
	 * @transient
	 * @var \TYPO3\TypoScript\RenderingContext
	 */
	protected $renderingContext;

	/**
	 * @param \TYPO3\TypoScript\RenderingContext $renderingContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRenderingContext(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		if (!$renderingContext instanceof \TYPO3\TypoScript\RenderingContext) {
			throw new \InvalidArgumentException('Plugin only supports \TYPO3\TypoScript\RenderingContext as a rendering context.', 1292502116);
		}
		$this->renderingContext = $renderingContext;
	}

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
	 * @param string $package
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
	 * Returns the rendered content of this plugin
	 *
	 * @return string The rendered content as a string
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render() {
		$parentRequest = $this->renderingContext->getControllerContext()->getRequest();
		$argumentNamespace = $this->getPluginNamespace();
		$pluginRequest = $this->subRequestBuilder->build($parentRequest, $argumentNamespace);

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
			if (!$pluginRequest->hasArgument($propertyName)) {
				$pluginRequest->setArgument($propertyName, $propertyValue);
			}
		}

		$parentResponse = $this->renderingContext->getControllerContext()->getResponse();
		$pluginResponse = new \TYPO3\FLOW3\MVC\Web\SubResponse($parentResponse);

		try {
			$this->dispatcher->dispatch($pluginRequest, $pluginResponse);

			return $this->contentElementWrappingService->wrapContentObject($this->node, $pluginResponse->getContent());
		} catch (\TYPO3\FLOW3\MVC\Exception\StopActionException $stopActionException) {
			throw $stopActionException;
		} catch (\Exception $exception) {
			$this->systemLogger->logException($exception);
			$message = 'Exception #' . $exception->getCode() . ' thrown while rendering ' . get_class($this) . '. See log for more details.';
			return ($this->objectManager->getContext() === 'Development') ? ('<strong>' . $message . '</strong>') : ('<!--' . $message . '-->');
		}
	}

	/**
	 * Returns the plugin namespace that will be prefixed to plugin parameters in URIs.
	 * By default this is typo3_<package>_<subpackage>_<pluginname>
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @todo make this configurable
	 */
	protected function getPluginNamespace() {
		$nodeArgumentNamespace = $this->node->getProperty('argumentNamespace');
		if ($nodeArgumentNamespace !== NULL) {
			return $nodeArgumentNamespace;
		} elseif ($this->argumentNamespace !== NULL) {
			return $this->argumentNamespace;
		}
		return strtolower(str_replace('\\', '_', get_class($this)));
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->render();
	}
}
?>
