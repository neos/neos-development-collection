<?php
namespace TYPO3\TypoScript\View;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TypoScript".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * View for using TypoScript for standard MVC controllers.
 *
 * Loads all TypoScript files from the current package Resources/Private/TypoScript
 * folder (recursively); and then checks whether a TypoScript object for current
 * controller and action can be found.
 *
 * If the controller class name is Foo\Bar\Baz\Controller\BlahController and the action is "index",
 * it checks for the TypoScript path Foo.Bar.Baz.BlahController.index.
 * If this path is found, then it is used for rendering. Otherwise, the $fallbackView
 * is used.
 */
class TypoScriptView extends \TYPO3\FLOW3\Mvc\View\AbstractView {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TypoScript\Core\Parser
	 */
	protected $typoScriptParser;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\View\ViewInterface
	 */
	protected $fallbackView;

	/**
	 * TypoScript files will be recursively loaded from this path
	 *
	 * @var string
	 */
	protected $typoScriptPathPattern = 'resource://@package/Private/TypoScript/';

	/**
	 * The parsed TypoScript array in its internal representation
	 *
	 * @var array
	 */
	protected $parsedTypoScript;

	/**
	 * The TypoScript path which should be rendered; derived from the controller
	 * and action names.
	 *
	 * @var string
	 */
	protected $typoScriptPath;

	/**
	 * Render the view
	 *
	 * @return string The rendered view
	 * @api
	 */
	public function render() {
		$this->loadTypoScript();

		$this->initializeTypoScriptPathForCurrentRequest();

		if ($this->isTypoScriptFoundForCurrentRequest()) {
			return $this->renderTypoScript();
		} else {
			return $this->renderFallbackView();
		}
	}

	/**
	 * Load TypoScript from the directory specified by $this->typoScriptPathPattern
	 *
	 * @return void
	 */
	protected function loadTypoScript() {
		$typoScriptPathPattern = str_replace('@package', $this->controllerContext->getRequest()->getControllerPackageKey(), $this->typoScriptPathPattern);
		$mergedTypoScriptCode = '';
		foreach (\TYPO3\FLOW3\Utility\Files::readDirectoryRecursively($typoScriptPathPattern, '.ts2') as $filePath) {
			$mergedTypoScriptCode .= PHP_EOL . file_get_contents($filePath) . PHP_EOL;
		}
		$this->typoScriptParser->setDefaultNamespace('TYPO3\TYPO3\TypoScript');
		$this->parsedTypoScript = $this->typoScriptParser->parse($mergedTypoScriptCode);
	}

	/**
	 * Initialize $this->typoScriptPath depending on the current controller and action
	 *
	 * @return void
	 */
	protected function initializeTypoScriptPathForCurrentRequest() {
		$request = $this->controllerContext->getRequest();
		$typoScriptPathForCurrentRequest = $request->getControllerObjectName();
		$typoScriptPathForCurrentRequest = str_replace('\\Controller\\', '\\', $typoScriptPathForCurrentRequest);
		$typoScriptPathForCurrentRequest = str_replace('\\', '.', $typoScriptPathForCurrentRequest);
		$typoScriptPathForCurrentRequest = trim($typoScriptPathForCurrentRequest, '.');
		$typoScriptPathForCurrentRequest .= '.' . $request->getControllerActionName();

		$this->typoScriptPath = $typoScriptPathForCurrentRequest;
	}

	/**
	 * Determine whether we are able to find TypoScript at the requested position
	 *
	 * @return boolean TRUE if TypoScript exists at $this->typoScriptPath; FALSE otherwise
	 */
	protected function isTypoScriptFoundForCurrentRequest() {
		return (\TYPO3\FLOW3\Utility\Arrays::getValueByPath($this->parsedTypoScript, $this->typoScriptPath) !== NULL);
	}

	/**
	 * Render the given TypoScript and return the rendered page
	 *
	 * @return string
	 */
	protected function renderTypoScript() {
		$typoScriptRuntime = new \TYPO3\TypoScript\Core\Runtime($this->parsedTypoScript, $this->controllerContext);
		$typoScriptRuntime->pushContextArray($this->variables);
		$output = $typoScriptRuntime->render(str_replace('.', '/', $this->typoScriptPath));
		$typoScriptRuntime->popContext();
		return $output;
	}

	/**
	 * Initialize and render the fallback view
	 *
	 * @return string
	 */
	public function renderFallbackView() {
		$this->fallbackView->setControllerContext($this->controllerContext);
		$this->fallbackView->assignMultiple($this->variables);
		return $this->fallbackView->render();
	}
}
?>