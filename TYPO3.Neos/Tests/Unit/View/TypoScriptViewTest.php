<?php
namespace TYPO3\Neos\Tests\Unit\View;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\TypoScriptService;
use TYPO3\Neos\View\TypoScriptView;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Core\Runtime;

/**
 * Testcase for the TypoScript View
 *
 */
class TypoScriptViewTest extends UnitTestCase
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
     * @var TypoScriptView
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

        $mockTypoScriptService = $this->createMock(TypoScriptService::class);
        $mockTypoScriptService->expects($this->any())->method('createRuntime')->will($this->returnValue($this->mockRuntime));

        $this->mockView = $this->getAccessibleMock(TypoScriptView::class, array('getClosestDocumentNode'));
        $this->mockView->expects($this->any())->method('getClosestDocumentNode')->will($this->returnValue($this->mockContextualizedNode));

        $this->inject($this->mockView, 'controllerContext', $mockControllerContext);
        $this->inject($this->mockView, 'securityContext', $this->mockSecurityContext);
        $this->inject($this->mockView, 'typoScriptService', $mockTypoScriptService);

        $this->mockView->_set('variables', array('value' => $this->mockContextualizedNode));
    }

    /**
     * @expectedException \TYPO3\Neos\Exception
     * @test
     */
    public function attemptToRenderWithoutNodeInformationAtAllThrowsException()
    {
        $view = $this->getAccessibleMock(TypoScriptView::class, array('dummy'));
        $view->render();
    }

    /**
     * @expectedException \TYPO3\Neos\Exception
     * @test
     */
    public function attemptToRenderWithInvalidNodeInformationThrowsException()
    {
        $view = $this->getAccessibleMock(TypoScriptView::class, array('dummy'));
        $view->_set('variables', array('value' => 'foo'));
        $view->render();
    }

    /**
     * @test
     */
    public function renderPutsSiteNodeInTypoScriptContext()
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

        $mockTypoScriptService = $this->createMock(TypoScriptService::class);
        $mockTypoScriptService->expects($this->any())->method('createRuntime')->will($this->returnValue($mockRuntime));

        $mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $view = $this->getAccessibleMock(TypoScriptView::class, array('getClosestDocumentNode'));
        $view->expects($this->any())->method('getClosestDocumentNode')->will($this->returnValue($mockContextualizedNode));

        $this->inject($view, 'securityContext', $mockSecurityContext);

        $this->inject($view, 'controllerContext', $mockControllerContext);
        $this->inject($view, 'typoScriptService', $mockTypoScriptService);

        $view->_set('variables', array('value' => $mockContextualizedNode));

        $mockResponse->expects($this->atLeastOnce())->method('setHeader')->with('Content-Type', ['application/json']);

        $output = $view->render();
        $this->assertEquals('Message body', $output);
    }
}
