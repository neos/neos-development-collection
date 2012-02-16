<?php
namespace TYPO3\TYPO3\Tests\Unit\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Link Node view helper
 *
 */
class NodeViewHelperTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3\ViewHelpers\Link\NodeViewHelper
	 */
	protected $viewHelper;

	/**
	 * Set up common mocks and object under test
	 */
	public function setUp() {
		$this->request = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$this->request->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('MyPackage'));
		$this->uriBuilder = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\UriBuilder');
		$this->controllerContext = $this->getMock('TYPO3\FLOW3\MVC\Controller\ControllerContext', array(), array(), '', FALSE);
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
		$this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
		$this->tagBuilder = $this->getMock('TYPO3\Fluid\Core\ViewHelper\TagBuilder');
		$this->viewHelperVariableContainer = $this->getMock('TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer', array(), array(), '', FALSE);
		$this->viewHelper = $this->getAccessibleMock('TYPO3\TYPO3\ViewHelpers\Link\NodeViewHelper', array('renderChildren'));
		$renderingContext = new \TYPO3\Fluid\Core\Rendering\RenderingContext();
		$renderingContext->setControllerContext($this->controllerContext);
		$renderingContext->injectViewHelperVariableContainer($this->viewHelperVariableContainer);
		$this->viewHelper->setRenderingContext($renderingContext);
		$this->viewHelper->injectTagBuilder($this->tagBuilder);
	}

	/**
	 * @test
	 */
	public function renderWithoutNodeGeneratesLinkToCurrentNode() {
		$currentNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);

		$context = $this->getMock('TYPO3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getCurrentNode')->will($this->returnValue($currentNode));

		$this->request->expects($this->once())->method('getFormat')->will($this->returnValue('xml.rss'));

		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('TYPO3\TYPO3', 'contentContext')->will($this->returnValue($context));

		$this->uriBuilder->expects($this->once())->method('reset')->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(FALSE)->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setFormat')->with('xml.rss')->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('uriFor')->with(NULL, array('node' => $currentNode))->will($this->returnValue('http://someuri/path'));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('href', 'http://someuri/path');
		$this->tagBuilder->expects($this->once())->method('render')->will($this->returnValue('tag output'));

		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Child content'));

		$output = $this->viewHelper->render();
		$this->assertEquals('tag output', $output);
	}

	/**
	 * @test
	 */
	public function renderWithNodeGeneratesLinkToGivenNode() {
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);

		$this->uriBuilder->expects($this->once())->method('reset')->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(FALSE)->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setFormat')->with(NULL)->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('uriFor')->with(NULL, array('node' => $node))->will($this->returnValue('http://someuri/path'));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('href', 'http://someuri/path');
		$this->tagBuilder->expects($this->once())->method('render')->will($this->returnValue('tag output'));

		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Child content'));

		$output = $this->viewHelper->render($node);
		$this->assertEquals('tag output', $output);
	}
}
?>