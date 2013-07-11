<?php
namespace TYPO3\TypoScript\Core;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Arrays;

/**
 * This runtime factory takes care of instantiating a TypoScript runtime.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class RuntimeFactory {

	/**
	 * @param array $typoScriptConfiguration
	 * @param ControllerContext $controllerContext
	 * @return Runtime
	 */
	public function create($typoScriptConfiguration, ControllerContext $controllerContext = NULL) {
		if ($controllerContext === NULL) {
			$controllerContext = $this->createControllerContextFromEnvironment();
		}

		return new Runtime($typoScriptConfiguration, $controllerContext);
	}

	/**
	 * @return ControllerContext
	 */
	protected function createControllerContextFromEnvironment() {
		$httpRequest = Request::createFromEnvironment();

		/** @var ActionRequest $request */
		$request = $httpRequest->createActionRequest();

		$uriBuilder = new UriBuilder();
		$uriBuilder->setRequest($request);

		return new \TYPO3\Flow\Mvc\Controller\ControllerContext(
			$request,
			new Response(),
			new Arguments(array()),
			$uriBuilder
		);
	}

}
?>