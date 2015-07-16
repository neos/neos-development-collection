<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\Routing\UriBuilder;

/**
 * Testcase for the TypoScript View
 *
 */
abstract class AbstractTypoScriptObjectTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var ControllerContext
	 */
	protected $controllerContext;

	/**
	 * Helper to build a TypoScript view object
	 *
	 * @return \TYPO3\TypoScript\View\TypoScriptView
	 */
	protected function buildView() {
		$view = new \TYPO3\TypoScript\View\TypoScriptView();

		$httpRequest = Request::createFromEnvironment();
		$request = $httpRequest->createActionRequest();

		$uriBuilder = new UriBuilder();
		$uriBuilder->setRequest($request);

		$this->controllerContext = new ControllerContext(
			$request,
			new Response(),
			new Arguments(array()),
			$uriBuilder
		);

		$view->setControllerContext($this->controllerContext);
		$view->disableFallbackView();
		$view->setPackageKey('TYPO3.TypoScript');
		$view->setTypoScriptPathPattern(__DIR__ . '/Fixtures/TypoScript');
		$view->assign('fixtureDirectory', __DIR__ . '/Fixtures/');

		return $view;
	}

	/**
	 * Used for TypoScript objects / Eel and plain value interoperability testing.
	 * Renders TypoScripts in the following paths and expects given $expected as result each time:
	 * $basePath . 'TypoScript'
	 * $basePath . 'Eel'
	 * $basePath . 'PlainValue'
	 *
	 * @param string $expected
	 * @param string $basePath
	 */
	protected function assertMultipleTypoScriptPaths($expected, $basePath) {
		$this->assertTyposcriptPath($expected, $basePath . 'Eel');
		$this->assertTyposcriptPath($expected, $basePath . 'PlainValue');
		$this->assertTyposcriptPath($expected, $basePath . 'TypoScript');
	}

	/**
	 * Renders the given TypoScript path and asserts that the result is the same es the given expected.
	 *
	 * @param string $expected
	 * @param string $path
	 */
	protected function assertTypoScriptPath($expected, $path) {
		$view = $this->buildView();
		$view->setTypoScriptPath($path);
		$this->assertSame($expected, $view->render(), 'TypoScript at path "' . $path . '" produced wrong results.');
	}

}
