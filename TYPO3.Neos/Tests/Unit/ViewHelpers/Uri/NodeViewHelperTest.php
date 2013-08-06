<?php
namespace TYPO3\Neos\Tests\Unit\ViewHelpers\Uri;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Neos\Domain\Service\ContentContext;

/**
 * Testcase for the Link.Node view helper
 *
 */
class NodeViewHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var  \TYPO3\TypoScript\Core\Runtime
	 */
	protected $tsRuntime;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
	 */
	protected $uriBuilderMock;

	/**
	 * Set up common mocks and object under test
	 */
	public function setUp() {
		$this->uriBuilderMock = $this->getMock('TYPO3\Flow\Mvc\Routing\UriBuilder', array('build'));
		$this->uriBuilderMock->expects($this->any())->method('build')->will($this->returnValue('dummy/final/url'));
		$parentHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->request = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('dummy'), array($parentHttpRequest));
		$this->request->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->request));
		$this->controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
		$this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilderMock));
		$this->viewHelper = $this->getAccessibleMock('TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper', array('dummy'));

		$this->inject($this->viewHelper, 'controllerContext', $this->controllerContext);

		$this->tsRuntime = $this->getAccessibleMock('TYPO3\TypoScript\Core\Runtime', array('getCurrentContext'), array(), '', FALSE);
		$fluidTsObject = $this->getAccessibleMock('\TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation', array('getTsRuntime'), array(), '', FALSE);
		$fluidTsObject->expects($this->any())->method('getTsRuntime')->will($this->returnValue($this->tsRuntime));
		$templateVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer(array('fluidTemplateTsObject' => $fluidTsObject));
		$this->inject($this->viewHelper, 'templateVariableContainer', $templateVariableContainer);
	}

	/**
	 * @test
	 */
	public function viewHelperUsesNodeInstanceWhenGiven() {
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $node,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));

		$this->viewHelper->render($node);
	}

	/**
	 * @test
	 */
	public function viewHelperUsesDocumentNodeFromContextIfNoNodeGiven() {
		$documentNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$this->tsRuntime->expects($this->atLeastOnce())->method('getCurrentContext')->will($this->returnValue(array('documentNode' => $documentNode)));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $documentNode,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));

		$this->viewHelper->render(NULL);
	}

	/**
	 * @test
	 */
	public function viewHelperFetchesNodeWithRelativePathFromDocumentNodeInContextWhenNodeIsGivenAsRelativePathString() {
		$documentNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$relativeNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$this->tsRuntime->expects($this->atLeastOnce())->method('getCurrentContext')->will($this->returnValue(array('documentNode' => $documentNode)));

		$documentNode->expects($this->any())->method('getNode')->with('some/relative/path')->will($this->returnValue($relativeNode));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $relativeNode,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));

		$this->viewHelper->render('some/relative/path');
	}

	/**
	 * @test
	 */
	public function viewHelperFetchesNodeWithRelativePathFromDocumentNodeSiteNodeWhenNodeIsGivenAsStringWithTilde() {
		$currentSiteNodeMock = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$documentNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$relativeNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$this->tsRuntime->expects($this->atLeastOnce())->method('getCurrentContext')->will($this->returnValue(array('documentNode' => $documentNode)));

		$currentSiteNodeMock->expects($this->atLeastOnce())->method('getNode')->with('some/site/path')->will($this->returnValue($relativeNode));
		$contentContext = $this->getMock('TYPO3\Neos\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$contentContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($currentSiteNodeMock));

		$documentNode->expects($this->any())->method('getContext')->will($this->returnValue($contentContext));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $relativeNode,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));


		$this->viewHelper->render('~/some/site/path');
	}
}
?>