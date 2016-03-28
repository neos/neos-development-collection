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
use TYPO3\Flow\Security\Context;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\View\TypoScriptView;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TypoScript\Core\Runtime;

/**
 * Testcase for the TypoScript View
 *
 */
class TypoScriptViewTest extends \TYPO3\Flow\Tests\UnitTestCase
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
        $this->mockContext = $this->getMock('TYPO3\Neos\Domain\Service\ContentContext', array(), array(), '', false);

        $mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array(), '', false);
        $this->mockContextualizedNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array('getContext'), array($mockNode, $this->mockContext));
        $mockSiteNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

        $this->mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($mockSiteNode));
        $this->mockContext->expects($this->any())->method('getDimensions')->will($this->returnValue(array()));

        $this->mockContextualizedNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContext));

        $this->mockRuntime = $this->getMock('TYPO3\TypoScript\Core\Runtime', array(), array(), '', false);

        $mockControllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', false);

        $this->mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);

        $mockTypoScriptService = $this->getMock('TYPO3\Neos\Domain\Service\TypoScriptService');
        $mockTypoScriptService->expects($this->any())->method('createRuntime')->will($this->returnValue($this->mockRuntime));

        $this->mockView = $this->getAccessibleMock('TYPO3\Neos\View\TypoScriptView', array('getClosestDocumentNode'));
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
        $view = $this->getAccessibleMock('TYPO3\Neos\View\TypoScriptView', array('dummy'));
        $view->render();
    }

    /**
     * @expectedException \TYPO3\Neos\Exception
     * @test
     */
    public function attemptToRenderWithInvalidNodeInformationThrowsException()
    {
        $view = $this->getAccessibleMock('TYPO3\Neos\View\TypoScriptView', array('dummy'));
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
    public function accountIsNotPushedToContextIfSecurityContextCanNotBeInitialized()
    {
        $this->setUpMockView();
        $this->mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(false));
        $this->mockSecurityContext->expects($this->never())->method('getAccount');
        $this->mockView->render();
    }

    /**
     * @test
     */
    public function accountIsPushedToContextIfSecurityContextCanBeInitialized()
    {
        $this->setUpMockView();
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account', array(), array(), '', false);

        $this->mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(true));
        $this->mockSecurityContext->expects($this->once())->method('getAccount')->will($this->returnValue($mockAccount));
        $this->mockView->render();
    }

    /**
     * @test
     */
    public function renderMergesHttpResponseIfOutputIsHttpMessage()
    {
        $mockContext = $this->getMock('TYPO3\Neos\Domain\Service\ContentContext', array(), array(), '', false);

        $mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array(), '', false);
        $mockContextualizedNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array('getContext'), array($mockNode, $mockContext));
        $mockSiteNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

        $mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($mockSiteNode));
        $mockContext->expects($this->any())->method('getDimensions')->will($this->returnValue(array()));

        $mockContextualizedNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));

        $mockResponse = $this->getMock('TYPO3\Flow\Http\Response');

        $mockControllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', false);
        $mockControllerContext->expects($this->any())->method('getResponse')->will($this->returnValue($mockResponse));

        $mockRuntime = $this->getMock('TYPO3\TypoScript\Core\Runtime', array(), array(), '', false);
        $mockRuntime->expects($this->any())->method('render')->will($this->returnValue("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\nMessage body"));
        $mockRuntime->expects($this->any())->method('getControllerContext')->will($this->returnValue($mockControllerContext));

        $mockTypoScriptService = $this->getMock('TYPO3\Neos\Domain\Service\TypoScriptService');
        $mockTypoScriptService->expects($this->any())->method('createRuntime')->will($this->returnValue($mockRuntime));

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);

        $view = $this->getAccessibleMock('TYPO3\Neos\View\TypoScriptView', array('getClosestDocumentNode'));
        $view->expects($this->any())->method('getClosestDocumentNode')->will($this->returnValue($mockContextualizedNode));

        $this->inject($view, 'securityContext', $mockSecurityContext);

        $this->inject($view, 'controllerContext', $mockControllerContext);
        $this->inject($view, 'typoScriptService', $mockTypoScriptService);

        $view->_set('variables', array('value' => $mockContextualizedNode));

        $mockResponse->expects($this->atLeastOnce())->method('setHeader')->with('Content-Type', 'application/json');

        $output = $view->render();
        $this->assertEquals('Message body', $output);
    }
}
