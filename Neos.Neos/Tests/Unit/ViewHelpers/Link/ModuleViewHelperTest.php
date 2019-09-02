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
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 */
class ModuleViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var MockObject|ModuleViewHelper
     */
    protected $viewHelper;

    /**
     * @var MockObject|TagBuilder
     */
    protected $tagBuilder;

    /**
     * @var MockObject|UriModuleViewHelper
     */
    protected $uriModuleViewHelper;

    /**
     * @var MockObject|RenderingContextInterface
     */
    protected $dummyRenderingContext;

    /**
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(ModuleViewHelper::class, ['renderChildren']);
        $this->tagBuilder = $this->createMock(TagBuilder::class);
        $this->tagBuilder->expects(self::once())->method('render')->willReturn('renderingResult');
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
        $this->uriModuleViewHelper->expects(self::once())->method('setRenderingContext')->with($this->dummyRenderingContext);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function callingRenderCallsTheUriModuleViewHelpersSetArgumentsMethodWithTheCorrectArguments(): void
    {
        $this->uriModuleViewHelper->expects(self::once())->method('setArguments')->with([
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
        $this->uriModuleViewHelper->expects(self::once())->method('render')->willReturn('moduleUri');
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('href', 'moduleUri');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function callingRenderSetsTheTagBuildersContentWithRenderChildrenResult(): void
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->willReturn('renderChildrenResult');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('renderChildrenResult');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function callingRenderSetsForceClosingTagOnTagBuilder(): void
    {
        $this->tagBuilder->expects(self::once())->method('forceClosingTag')->with(true);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function callingRenderReturnsTagBuildersRenderResult(): void
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['path' => 'path']);
        self::assertSame('renderingResult', $this->viewHelper->render());
    }
}
