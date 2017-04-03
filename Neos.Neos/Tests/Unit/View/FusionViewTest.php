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
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\View\FusionView;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\Core\Runtime;

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
        $this->mockContextualizedNode = $this->getMockBuilder(Node::class)->setMethods(array('getContext'))->setConstructorArgs(array($mockNode, $this->mockContext))->getMock();
        $mockSiteNode = $this->createMock(NodeInterface::class);

        $this->mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($mockSiteNode));
        $this->mockContext->expects($this->any())->method('getDimensions')->will($this->returnValue(array()));

        $this->mockContextualizedNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContext));

        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $mockFusionService = $this->createMock(FusionService::class);
        $mockFusionService->expects($this->any())->method('createRuntime')->will($this->returnValue($this->mockRuntime));

        $this->mockView = $this->getAccessibleMock(FusionView::class, array('getClosestDocumentNode'));
        $this->mockView->expects($this->any())->method('getClosestDocumentNode')->will($this->returnValue($this->mockContextualizedNode));

        $this->inject($this->mockView, 'controllerContext', $mockControllerContext);
        $this->inject($this->mockView, 'securityContext', $this->mockSecurityContext);
        $this->inject($this->mockView, 'fusionService', $mockFusionService);

        $this->mockView->_set('variables', array('value' => $this->mockContextualizedNode));
    }

    /**
     * @expectedException \Neos\Neos\Exception
     * @test
     */
    public function attemptToRenderWithoutNodeInformationAtAllThrowsException()
    {
        $view = $this->getAccessibleMock(FusionView::class, array('dummy'));
        $view->render();
    }

    /**
     * @expectedException \Neos\Neos\Exception
     * @test
     */
    public function attemptToRenderWithInvalidNodeInformationThrowsException()
    {
        $view = $this->getAccessibleMock(FusionView::class, array('dummy'));
        $view->_set('variables', array('value' => 'foo'));
        $view->render();
    }

    /**
     * @test
     */
    public function renderPutsSiteNodeInFusionContext()
    {
        $this->setUpMockView();
        $this->mockRuntime->expects($this->once())->method('pushContextArray')->with($this->arrayHasKey('site'));
        $this->mockView->render();
    }

    /**
     * @test
     */
    public function renderMergesHttpResponseIfOutputIsHttpMessage()
    {
        $mockContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();

        $mockNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockContextualizedNode = $this->getMockBuilder(Node::class)->setMethods(array('getContext'))->setConstructorArgs(array($mockNode, $mockContext))->getMock();
        $mockSiteNode = $this->createMock(NodeInterface::class);

        $mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($mockSiteNode));
        $mockContext->expects($this->any())->method('getDimensions')->will($this->returnValue(array()));

        $mockContextualizedNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));

        $mockResponse = $this->createMock(Response::class);

        $mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $mockControllerContext->expects($this->any())->method('getResponse')->will($this->returnValue($mockResponse));

        $mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $mockRuntime->expects($this->any())->method('render')->will($this->returnValue("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\nMessage body"));
        $mockRuntime->expects($this->any())->method('getControllerContext')->will($this->returnValue($mockControllerContext));

        $mockFusionService = $this->createMock(FusionService::class);
        $mockFusionService->expects($this->any())->method('createRuntime')->will($this->returnValue($mockRuntime));

        $mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $view = $this->getAccessibleMock(FusionView::class, array('getClosestDocumentNode'));
        $view->expects($this->any())->method('getClosestDocumentNode')->will($this->returnValue($mockContextualizedNode));

        $this->inject($view, 'securityContext', $mockSecurityContext);

        $this->inject($view, 'controllerContext', $mockControllerContext);
        $this->inject($view, 'fusionService', $mockFusionService);

        $view->_set('variables', array('value' => $mockContextualizedNode));

        $mockResponse->expects($this->atLeastOnce())->method('setHeader')->with('Content-Type', 'application/json');

        $output = $view->render();
        $this->assertEquals('Message body', $output);
    }
}
