<?php
namespace TYPO3\Neos\Tests\Unit\ViewHelpers\Link;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\ViewHelpers\Link\ModuleViewHelper;
use TYPO3\Neos\ViewHelpers\Uri\ModuleViewHelper as UriModuleViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 */
class ModuleViewHelperTest extends UnitTestCase
{
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
    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(ModuleViewHelper::class, array('renderChildren'));
        $this->tagBuilder = $this->createMock(TagBuilder::class);
        $this->uriModuleViewHelper = $this->getMockBuilder(\TYPO3\Neos\ViewHelpers\Uri\ModuleViewHelper::class)->setMethods(array('setRenderingContext', 'render'))->getMock();

        $this->dummyRenderingContext = $this->createMock(RenderingContextInterface::class);
        $this->inject($this->viewHelper, 'renderingContext', $this->dummyRenderingContext);

        $this->inject($this->viewHelper, 'tag', $this->tagBuilder);
        $this->inject($this->viewHelper, 'uriModuleViewHelper', $this->uriModuleViewHelper);
    }

    /**
     * @test
     */
    public function callingRenderSetsTheRenderingContextOnTheUriViewHelper()
    {
        $this->uriModuleViewHelper->expects($this->once())->method('setRenderingContext')->with($this->dummyRenderingContext);
        $this->viewHelper->render('path');
    }

    /**
     * @test
     */
    public function callingRenderCallsTheUriModuleViewHelpersRenderMethodWithTheCorrectArguments()
    {
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
    public function callingRenderAddsUriViewHelpersReturnAsTagHrefAttributeIfItsNotEmpty()
    {
        $this->uriModuleViewHelper->expects($this->once())->method('render')->will($this->returnValue('SomethingNotNull'));
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('href', 'SomethingNotNull');
        $this->viewHelper->render('path');
    }

    /**
     * @test
     */
    public function callingRenderSetsTheTagBuildersContentWithRenderChildrenResult()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('renderChildrenResult'));
        $this->tagBuilder->expects($this->once())->method('setContent')->with('renderChildrenResult');
        $this->viewHelper->render('path');
    }

    /**
     * @test
     */
    public function callingRenderSetsForceClosingTagOnTagBuilder()
    {
        $this->tagBuilder->expects($this->once())->method('forceClosingTag')->with(true);
        $this->viewHelper->render('path');
    }

    /**
     * @test
     */
    public function callingRenderReturnsTagBuildersRenderResult()
    {
        $this->tagBuilder->expects($this->once())->method('render')->will($this->returnValue('renderingResult'));
        $this->assertSame('renderingResult', $this->viewHelper->render('path'));
    }
}
