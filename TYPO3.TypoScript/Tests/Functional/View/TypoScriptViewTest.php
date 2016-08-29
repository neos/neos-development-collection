<?php
namespace TYPO3\TypoScript\Tests\Functional\View;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TypoScript\View\TypoScriptView;

/**
 * Testcase for the TypoScript View
 *
 */
class TypoScriptViewTest extends FunctionalTestCase
{
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
    public function setUp()
    {
        $this->mockFallbackView = $this->createMock('TYPO3\Flow\Mvc\View\ViewInterface');
        $this->mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function typoScriptViewIsUsedForRendering()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $this->assertEquals('X', $view->render());
    }

    /**
     * @test
     */
    public function typoScriptViewUsesGivenPathIfSet()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->setTypoScriptPath('foo/bar');
        $this->assertEquals('Xfoobar', $view->render());
    }

    /**
     * @test
     */
    public function ifNoTypoScriptViewIsFoundThenFallbackViewIsExecuted()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'nonExisting');
        $this->mockFallbackView->expects($this->once())->method('render')->will($this->returnValue('FallbackView called'));
        $this->mockFallbackView->expects($this->once())->method('setControllerContext')->with($this->mockControllerContext);

        $this->assertEquals('FallbackView called', $view->render());
    }

    /**
     * @test
     */
    public function typoScriptViewOutputsVariable()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->assign('test', 'Hallo Welt');
        $this->assertEquals('XHallo Welt', $view->render());
    }

    /**
     * Prepare a TypoScriptView for testing that Mocks a request with the given controller and action names.
     *
     * @param string $controllerObjectName
     * @param string $controllerActionName
     * @return TypoScriptView
     */
    protected function buildView($controllerObjectName, $controllerActionName)
    {
        $request = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $request->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $view = new TypoScriptView();
        $view->setControllerContext($this->mockControllerContext);
        $this->inject($view, 'fallbackView', $this->mockFallbackView);
        $view->setTypoScriptPathPattern(__DIR__ . '/Fixtures/TypoScript');

        return $view;
    }
}
