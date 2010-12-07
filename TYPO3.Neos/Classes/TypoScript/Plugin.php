<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A TypoScript Plugin object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Plugin extends \F3\TypoScript\AbstractObject implements \F3\TypoScript\ContentObjectInterface {

	/**
	 * @inject
	 * @var \F3\FLOW3\MVC\Web\SubRequestBuilder
	 */
	protected $subRequestBuilder;

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @inject
	 * @var \F3\FLOW3\MVC\Dispatcher
	 */
	protected $dispatcher;

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
	protected $action = 'index';

	/**
	 * The rendering context as passed to render()
	 *
	 * @transient
	 * @var \F3\TypoScript\RenderingContext
	 */
	protected $renderingContext;

	/**
	 * @param \F3\TypoScript\RenderingContext $renderingContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRenderingContext(\F3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		if (!$renderingContext instanceof \F3\TypoScript\RenderingContext) {
			throw new \InvalidArgumentException('Plugin only supports \F3\TypoScript\RenderingContext as a rendering context.', 1292502116);
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
			$pluginRequest->setControllerPackageKey($this->package);
		}
		if ($pluginRequest->getControllerSubpackageKey() === NULL) {
			$pluginRequest->setControllerSubpackageKey($this->subpackage);
		}
		if ($pluginRequest->getControllerName() === NULL) {
			$pluginRequest->setControllerName($this->controller);
		}
		if ($pluginRequest->getControllerActionName() === NULL) {
			$pluginRequest->setControllerActionName($this->action);
		}

		$parentResponse = $this->renderingContext->getControllerContext()->getResponse();
		$pluginResponse = $this->objectManager->create('F3\FLOW3\MVC\Web\SubResponse', $parentResponse);

		$this->dispatcher->dispatch($pluginRequest, $pluginResponse);
		return $pluginResponse->getContent();
	}

	/**
	 * Returns the plugin namespace that will be prefixed to plugin parameters in URIs.
	 * By default this is f3_<package>_<subpackage>_<pluginname>
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @todo make this configurable
	 */
	protected function getPluginNamespace() {
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
