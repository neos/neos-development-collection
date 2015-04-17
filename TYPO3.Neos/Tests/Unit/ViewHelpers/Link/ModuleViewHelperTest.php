<?php
namespace TYPO3\Neos\Tests\Unit\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\Neos\ViewHelpers\Link\ModuleViewHelper;
use TYPO3\Neos\ViewHelpers\Uri\ModuleViewHelper as UriModuleViewHelper;

/**
 */
class ModuleViewHelperTest extends UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|ModuleViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|TagBuilder
	 */
	protected $tagBuilder;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|UriModuleViewHelper
	 */
	protected $uriModuleViewHelper;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|RenderingContextInterface
	 */
	protected $dummyRenderingContext;

	/**
	 */
	protected function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getAccessibleMock('TYPO3\Neos\ViewHelpers\Link\ModuleViewHelper', array('renderChildren'));
		$this->tagBuilder = $this->getMock('TYPO3\Fluid\Core\ViewHelper\TagBuilder');
		$this->uriModuleViewHelper = $this->getMock('TYPO3\Neos\ViewHelpers\Uri\ModuleViewHelper', array('setRenderingContext', 'render'));

		$this->dummyRenderingContext = $this->getMock('TYPO3\Fluid\Core\Rendering\RenderingContextInterface');
		$this->inject($this->viewHelper, 'renderingContext', $this->dummyRenderingContext);

		$this->inject($this->viewHelper, 'tag', $this->tagBuilder);
		$this->inject($this->viewHelper, 'uriModuleViewHelper', $this->uriModuleViewHelper);
	}

	/**
	 * @test
	 */
	public function callingRenderSetsTheRenderingContextOnTheUriViewHelper() {
		$this->uriModuleViewHelper->expects($this->once())->method('setRenderingContext')->with($this->dummyRenderingContext);
		$this->viewHelper->render('path');
	}

	/**
	 * @test
	 */
	public function callingRenderCallsTheUriModuleViewHelpersRenderMethodWithTheCorrectArguments() {
		$this->uriModuleViewHelper->expects($this->once())->method('render')->with(
			'path', 'action', array('arguments'), 'section', 'format', array('additionalParams'), 'addQueryString', array('argumentsToBeExcludedFromQueryString')
		);
		$this->viewHelper->render(
			'path', 'action', array('arguments'), 'section', 'format', array('additionalParams'), 'addQueryString', array('argumentsToBeExcludedFromQueryString')
		);
	}

	/**
	 * @test
	 */
	public function callingRenderAddsUriViewHelpersReturnAsTagHrefAttributeIfItsNotEmpty() {
		$this->uriModuleViewHelper->expects($this->once())->method('render')->will($this->returnValue('SomethingNotNull'));
		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('href', 'SomethingNotNull');
		$this->viewHelper->render('path');
	}

	/**
	 * @test
	 */
	public function callingRenderSetsTheTagBuildersContentWithRenderChildrenResult() {
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('renderChildrenResult'));
		$this->tagBuilder->expects($this->once())->method('setContent')->with('renderChildrenResult');
		$this->viewHelper->render('path');
	}

	/**
	 * @test
	 */
	public function callingRenderSetsForceClosingTagOnTagBuilder() {
		$this->tagBuilder->expects($this->once())->method('forceClosingTag')->with(TRUE);
		$this->viewHelper->render('path');
	}

	/**
	 * @test
	 */
	public function callingRenderReturnsTagBuildersRenderResult() {
		$this->tagBuilder->expects($this->once())->method('render')->will($this->returnValue('renderingResult'));
		$this->assertSame('renderingResult', $this->viewHelper->render('path'));
	}

}
