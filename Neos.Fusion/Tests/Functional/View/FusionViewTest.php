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
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function fusionViewIsUsedForRendering()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        self::assertEquals('X', $view->render());
    }

    #[Test]
    public function fusionViewUsesGivenPathIfSet()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->setFusionPath('foo/bar');
        self::assertEquals('Xfoobar', $view->render());
    }

    #[Test]
    public function fusionViewOutputsVariable()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->assign('test', 'Hallo Welt');
        self::assertEquals('XHallo Welt', $view->render());
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
        $request->expects(self::any())->method('getControllerObjectName')->willReturn($controllerObjectName);
        $request->expects(self::any())->method('getControllerActionName')->willReturn($controllerActionName);
        $this->mockControllerContext->expects(self::any())->method('getRequest')->willReturn($request);

        $view = new FusionView();
        $view->setControllerContext($this->mockControllerContext);
        $view->setFusionPathPattern(__DIR__ . '/Fixtures/Fusion');

        return $view;
    }
}
