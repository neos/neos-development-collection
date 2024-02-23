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
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Fusion\Core\FusionGlobals;
use Neos\Fusion\View\FusionView;
use Psr\Http\Message\ResponseInterface;

/**
 * Testcase for the Fusion View
 *
 */
class FusionViewTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function fusionViewIsUsedForRendering()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        self::assertEquals('X', $view->render()->getBody()->getContents());
    }

    /**
     * @test
     */
    public function fusionViewUsesGivenPathIfSet()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->setFusionPath('foo/bar');
        self::assertEquals('Xfoobar', $view->render()->getBody()->getContents());
    }

    /**
     * @test
     */
    public function fusionViewOutputsVariable()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->assign('test', 'Hallo Welt');
        self::assertEquals('XHallo Welt', $view->render()->getBody()->getContents());
    }

    /**
     * @test
     */
    public function fusionVieReturnsHttpResponse()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->assign('test', 'Hallo Welt');
        $response = $view->render();
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertEquals('XHallo Welt', $view->render()->getBody()->getContents());
    }

    /**
     * @test
     */
    public function fusionViewReturnsHttpResponseFromHttpMessagePrototype()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->setFusionPath('response');
        $response = $view->render();
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame('{"some":"json"}', $response->getBody()->getContents());
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
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
        $request->expects(self::any())->method('getControllerObjectName')->will(self::returnValue($controllerObjectName));
        $request->expects(self::any())->method('getControllerActionName')->will(self::returnValue($controllerActionName));

        $view = new FusionView();
        $view->setOption('fusionGlobals', FusionGlobals::fromArray(['request' => $request]));
        $view->setFusionPathPattern(__DIR__ . '/Fixtures/Fusion');

        return $view;
    }
}
