<?php
namespace Neos\Neos\Tests\Unit\ViewHelpers\Link;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\Neos\ViewHelpers\Link\ModuleViewHelper;
use Neos\Neos\ViewHelpers\Uri\ModuleViewHelper as UriModuleViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 */
class ModuleViewHelperTest extends ViewHelperBaseTestcase
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
    public function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(ModuleViewHelper::class, ['renderChildren']);
        $this->tagBuilder = $this->createMock(TagBuilder::class);
        $this->tagBuilder->expects($this->once())->method('render')->willReturn('renderingResult');
        $this->uriModuleViewHelper = $this->getMockBuilder(UriModuleViewHelper::class)->setMethods(['setRenderingContext', 'setArguments', 'render'])->getMock();

        $this->dummyRenderingContext = $this->createMock(RenderingContextInterface::class);
        $this->inject($this->viewHelper, 'renderingContext', $this->dummyRenderingContext);

        $this->inject($this->viewHelper, 'tag', $this->tagBuilder);
        $this->inject($this->viewHelper, 'uriModuleViewHelper', $this->uriModuleViewHelper);
    }

    /**
     * @test
     */
    public function callingRenderSetsTheRenderingContextOnTheUriViewHelper(): void
    {
        $this->uriModuleViewHelper->expects($this->once())->method('setRenderingContext')->with($this->dummyRenderingContext);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function callingRenderCallsTheUriModuleViewHelpersSetArgumentsMethodWithTheCorrectArguments(): void
    {
        $this->uriModuleViewHelper->expects($this->once())->method('setArguments')->with([
            'path' => 'path',
            'action' => 'action',
            'arguments' => ['arguments'],
            'section' => 'section',
            'format' => 'format',
            'additionalParams' => ['additionalParams'],
            'addQueryString' => true,
            'argumentsToBeExcludedFromQueryString' => ['argumentsToBeExcludedFromQueryString']
        ]);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => 'path',
            'action' => 'action',
            'arguments' => ['arguments'],
            'section' => 'section',
            'format' => 'format',
            'additionalParams' => ['additionalParams'],
            'addQueryString' => true,
            'argumentsToBeExcludedFromQueryString' => ['argumentsToBeExcludedFromQueryString']
        ]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function callingRenderAddsUriViewHelpersReturnAsTagHrefAttributeIfItsNotEmpty(): void
    {
        $this->uriModuleViewHelper->expects($this->once())->method('render')->willReturn('moduleUri');
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('href', 'moduleUri');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function callingRenderSetsTheTagBuildersContentWithRenderChildrenResult(): void
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn('renderChildrenResult');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('renderChildrenResult');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function callingRenderSetsForceClosingTagOnTagBuilder(): void
    {
        $this->tagBuilder->expects($this->once())->method('forceClosingTag')->with(true);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function callingRenderReturnsTagBuildersRenderResult(): void
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        $this->assertSame('renderingResult', $this->viewHelper->render());
    }
}
