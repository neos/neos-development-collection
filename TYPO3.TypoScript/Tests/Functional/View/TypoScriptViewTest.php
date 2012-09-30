<?php
namespace TYPO3\TypoScript\Tests\Functional\View;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript View
 *
 */
class TypoScriptViewTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var TYPO3\Flow\Mvc\View\ViewInterface
	 */
	protected $mockFallbackView;

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $mockControllerContext;

	/**
	 * Initializer
	 */
	public function setUp() {
		$this->mockFallbackView = $this->getMock('TYPO3\Flow\Mvc\View\ViewInterface');
	}

	/**
	 * @test
	 */
	public function typoScriptViewIsUsedForRendering() {
		$view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
		$this->assertEquals('X', $view->render());
	}

	/**
	 * @test
	 */
	public function typoScriptViewUsesGivenPathIfSet() {
		$view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
		$view->setTypoScriptPath('foo/bar');
		$this->assertEquals('Xfoobar', $view->render());
	}

	/**
	 * @test
	 */
	public function ifNoTypoScriptViewIsFoundThenFallbackViewIsExecuted() {
		$view = $this->buildView('Foo\Bar\Controller\TestController', 'nonExisting');
		$this->mockFallbackView->expects($this->once())->method('render')->will($this->returnValue('FallbackView called'));
		$this->mockFallbackView->expects($this->once())->method('setControllerContext')->with($this->mockControllerContext);

		$this->assertEquals('FallbackView called', $view->render());
	}

	/**
	 * @test
	 */
	public function typoScriptViewOutputsVariable() {
		$view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
		$view->assign('test', 'Hallo Welt');
		$this->assertEquals('XHallo Welt', $view->render());
	}

	protected function buildView($controllerObjectName, $controllerActionName) {
		$request = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$request->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
		$request->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
		$this->mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
		$this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($request));

		$view = new \TYPO3\TypoScript\View\TypoScriptView();
		$view->setControllerContext($this->mockControllerContext);
		$this->inject($view, 'fallbackView', $this->mockFallbackView);

		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($view, 'typoScriptPathPattern', __DIR__ . '/Fixtures/TypoScript', TRUE);

		return $view;
	}
}
?>