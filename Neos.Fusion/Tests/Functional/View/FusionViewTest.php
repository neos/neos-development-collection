<?php
namespace Neos\Fusion\Tests\Functional\View;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Fusion\View\FusionView;

/**
 * Testcase for the Fusion View
 *
 */
class FusionViewTest extends FunctionalTestCase
{
    /**
     * @var ControllerContext
     */
    protected $mockControllerContext;

    /**
     * Initializer
     */
    public function setUp(): void
    {
        $this->mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function fusionViewIsUsedForRendering()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $this->assertEquals('X', $view->render());
    }

    /**
     * @test
     */
    public function fusionViewUsesGivenPathIfSet()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->setFusionPath('foo/bar');
        $this->assertEquals('Xfoobar', $view->render());
    }

    /**
     * @test
     */
    public function fusionViewOutputsVariable()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->assign('test', 'Hallo Welt');
        $this->assertEquals('XHallo Welt', $view->render());
    }

    /**
     * Prepare a FusionView for testing that Mocks a request with the given controller and action names.
     *
     * @param string $controllerObjectName
     * @param string $controllerActionName
     * @return FusionView
     */
    protected function buildView($controllerObjectName, $controllerActionName)
    {
        $request = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $request->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $view = new FusionView();
        $view->setControllerContext($this->mockControllerContext);
        $view->setFusionPathPattern(__DIR__ . '/Fixtures/Fusion');

        return $view;
    }
}
