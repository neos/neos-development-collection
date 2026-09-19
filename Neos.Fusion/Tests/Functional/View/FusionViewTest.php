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
use Neos\Fusion\Core\IllegalEntryFusionPathValueException;
use Neos\Fusion\View\FusionView;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Testcase for the Fusion View
 *
 */
class FusionViewTest extends FunctionalTestCase
{
    #[Test]
    public function fusionViewIsUsedForRendering()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        self::assertEquals('X', $view->render()->getContents());
    }

    #[Test]
    public function fusionViewUsesGivenPathIfSet()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->setFusionPath('foo/bar');
        self::assertEquals('Xfoobar', $view->render()->getContents());
    }

    #[Test]
    public function fusionViewOutputsVariable()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->assign('test', 'Hallo Welt');
        self::assertEquals('XHallo Welt', $view->render()->getContents());
    }

    #[Test]
    public function fusionViewReturnsStreamInterface()
    {
        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->assign('test', 'Hallo Welt');
        $response = $view->render();
        self::assertInstanceOf(StreamInterface::class, $response);
        self::assertEquals('XHallo Welt', $response->getContents());
    }

    #[Test]
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

    #[Test]
    public function fusionViewCannotRenderNonStringableValue()
    {
        $this->expectException(IllegalEntryFusionPathValueException::class);
        $this->expectExceptionMessage('Fusion entry path "illegalEntryPointValue" is expected to render a compatible http response body: string|\Stringable|null. Got array instead.');

        $view = $this->buildView('Foo\Bar\Controller\TestController', 'index');
        $view->setFusionPath('illegalEntryPointValue');
        $view->render();
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

        $view = new FusionView();
        $view->assign('request', $request);
        $view->setFusionPathPattern(__DIR__ . '/Fixtures/Fusion');

        return $view;
    }
}
