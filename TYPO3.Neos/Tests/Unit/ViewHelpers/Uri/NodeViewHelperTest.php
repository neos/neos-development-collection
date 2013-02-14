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
	 * Set up common mocks and object under test
	 */
	public function setUp() {
		$uriBuilderMock = $this->getMock('TYPO3\Flow\Mvc\Routing\UriBuilder', array('build'));
		$uriBuilderMock->expects($this->any())->method('build')->will($this->returnValue('dummy/final/url'));
		$parentHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->request = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('dummy'), array($parentHttpRequest));
		$this->request->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->request));
		$this->controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
		$this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($uriBuilderMock));
		$this->viewHelper = $this->getMock('TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper', array('dummy'));

		$this->inject($this->viewHelper, 'controllerContext', $this->controllerContext);
	}

	/**
	 * @test
	 */
	public function viewHelperFetchesCurrentNodeIfNotGiven() {
		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository');
		$nodeContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContextInterface');
		$nodeContext->expects($this->once())->method('getCurrentNode');
		$nodeRepository->expects($this->once())->method('getContext')->will($this->returnValue($nodeContext));
		$this->inject($this->viewHelper, 'nodeRepository', $nodeRepository);
		$this->viewHelper->render(NULL);
	}

	/**
	 * @test
	 */
	public function viewHelperFetchesRelativePathFromCurrentContextNodeWhenNodeIsGivenAsRelativePathString() {
		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository');
		$nodeContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContextInterface');

		$currentNodeMock = $this->getMock('TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface');
		$currentNodeMock->expects($this->once())->method('getNode')->with('some/relative/path');

		$nodeContext->expects($this->once())->method('getCurrentNode')->will($this->returnValue($currentNodeMock));
		$nodeRepository->expects($this->once())->method('getContext')->will($this->returnValue($nodeContext));

		$this->inject($this->viewHelper, 'nodeRepository', $nodeRepository);
		$this->viewHelper->render('some/relative/path');
	}

	/**
	 * @test
	 */
	public function viewHelperFetchesRelativePathFromCurrentContextSiteNodeWhenNodeIsGivenWithAStartingTilde() {
		$currentSiteNodeMock = $this->getMock('TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface');
		$currentSiteNodeMock->expects($this->once())->method('getNode')->will($this->returnValue($this->getMock('TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface')));
		$nodeContentContext = $this->getMock('TYPO3\Neos\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$nodeContentContext->expects($this->once())->method('getCurrentSiteNode')->will($this->returnValue($currentSiteNodeMock));
		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository');
		$nodeRepository->expects($this->once())->method('getContext')->will($this->returnValue($nodeContentContext));

		$this->inject($this->viewHelper, 'nodeRepository', $nodeRepository);
		$this->viewHelper->render('~/some/site/path');
	}
}
?>