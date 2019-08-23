<?php
namespace Neos\Neos\Tests\Unit\View;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Exception;
use Neos\Neos\View\FusionView;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\Fusion\Core\Runtime;
use Psr\Http\Message\ResponseInterface;

/**
 * Testcase for the Fusion View
 *
 */
class FusionViewTest extends UnitTestCase
{
    /**
     * @var ContentContext
     */
    protected $mockContext;

    /**
     * @var Context
     */
    protected $mockSecurityContext;

    /**
     * @var FusionView
     */
    protected $mockView;

    /**
     * @var Runtime
     */
    protected $mockRuntime;

    /**
     * @var Node
     */
    protected $mockContextualizedNode;

    /**
     * Sets up a view with context for testing
     *
     * @return void
     */
    public function setUpMockView()
    {
        $this->mockContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();

        $mockNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $this->mockContextualizedNode = $this->getMockBuilder(Node::class)->setMethods(['getContext'])->setConstructorArgs([$mockNode, $this->mockContext])->getMock();
        $mockSiteNode = $this->createMock(TraversableNodeInterface::class);

        $this->mockContext->expects(self::any())->method('getCurrentSiteNode')->will(self::returnValue($mockSiteNode));
        $this->mockContext->expects(self::any())->method('getDimensions')->will(self::returnValue([]));

        $this->mockContextualizedNode->expects(self::any())->method('getContext')->will(self::returnValue($this->mockContext));

        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $mockFusionService = $this->createMock(FusionService::class);
        $mockFusionService->expects(self::any())->method('createRuntime')->will(self::returnValue($this->mockRuntime));

        $this->mockView = $this->getAccessibleMock(FusionView::class, ['getClosestDocumentNode']);
        $this->mockView->expects(self::any())->method('getClosestDocumentNode')->will(self::returnValue($this->mockContextualizedNode));

        $this->inject($this->mockView, 'controllerContext', $mockControllerContext);
        $this->inject($this->mockView, 'securityContext', $this->mockSecurityContext);
        $this->inject($this->mockView, 'fusionService', $mockFusionService);

        $this->mockView->_set('variables', ['value' => $this->mockContextualizedNode]);
    }

    /**
     * @test
     */
    public function attemptToRenderWithoutNodeInformationAtAllThrowsException()
    {
        $this->expectException(Exception::class);
        $view = $this->getAccessibleMock(FusionView::class, ['dummy']);
        $view->render();
    }

    /**
     * @test
     */
    public function attemptToRenderWithInvalidNodeInformationThrowsException()
    {
        $this->expectException(Exception::class);
        $view = $this->getAccessibleMock(FusionView::class, ['dummy']);
        $view->_set('variables', ['value' => 'foo']);
        $view->render();
    }

    /**
     * @test
     */
    public function renderPutsSiteNodeInFusionContext()
    {
        $this->setUpMockView();
        $this->mockRuntime->expects(self::once())->method('pushContextArray')->with($this->arrayHasKey('site'));
        $this->mockView->render();
    }

    /**
     * @test
     */
    public function renderMergesHttpResponseIfOutputIsHttpMessage()
    {
        $mockContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();

        $mockNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockContextualizedNode = $this->getMockBuilder(Node::class)->setMethods(['getContext'])->setConstructorArgs([$mockNode, $mockContext])->getMock();
        $mockSiteNode = $this->createMock(TraversableNodeInterface::class);

        $mockContext->expects(self::any())->method('getCurrentSiteNode')->will(self::returnValue($mockSiteNode));
        $mockContext->expects(self::any())->method('getDimensions')->will(self::returnValue([]));

        $mockContextualizedNode->expects(self::any())->method('getContext')->will(self::returnValue($mockContext));

        $mockResponse = new ActionResponse();

        $mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $mockControllerContext->expects(self::any())->method('getResponse')->will(self::returnValue($mockResponse));

        $mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $mockRuntime->expects(self::any())->method('render')->will(self::returnValue("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\nMessage body"));
        $mockRuntime->expects(self::any())->method('getControllerContext')->will(self::returnValue($mockControllerContext));

        $mockFusionService = $this->createMock(FusionService::class);
        $mockFusionService->expects(self::any())->method('createRuntime')->will(self::returnValue($mockRuntime));

        $mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $view = $this->getAccessibleMock(FusionView::class, ['getClosestDocumentNode']);
        $view->expects(self::any())->method('getClosestDocumentNode')->will(self::returnValue($mockContextualizedNode));

        $this->inject($view, 'securityContext', $mockSecurityContext);

        $this->inject($view, 'controllerContext', $mockControllerContext);
        $this->inject($view, 'fusionService', $mockFusionService);

        $view->_set('variables', ['value' => $mockContextualizedNode]);

        /** @var ResponseInterface $output */
        $output = $view->render();

        // FIXME: Check for content type
        self::assertInstanceOf(ResponseInterface::class, $output);
        self::assertEquals('Message body', $output->getBody()->getContents());
    }
}
