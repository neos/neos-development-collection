<?php
namespace Neos\Neos\Tests\Unit\ViewHelpers\Uri;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\Neos\ViewHelpers\Uri\ModuleViewHelper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 */
class ModuleViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var MockObject|ModuleViewHelper
     */
    protected $viewHelper;

    /**
     * @var MockObject|UriBuilder
     */
    protected $uriBuilder;

    /**
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(ModuleViewHelper::class)->setMethods(['setMainRequestToUriBuilder'])->getMock();
        $this->uriBuilder = $this->createMock(UriBuilder::class);
        $this->inject($this->viewHelper, 'uriBuilder', $this->uriBuilder);
    }

    /**
     * @test
     */
    public function callingRenderAssignsVariablesCorrectlyToUriBuilder()
    {
        $this->uriBuilder->expects(self::once())->method('setSection')->with('section')->will(self::returnSelf());
        $this->uriBuilder->expects(self::once())->method('setArguments')->with(['additionalParams'])->will(self::returnSelf());
        $this->uriBuilder->expects(self::once())->method('setArgumentsToBeExcludedFromQueryString')->with(['argumentsToBeExcludedFromQueryString'])->will(self::returnSelf());
        $this->uriBuilder->expects(self::once())->method('setFormat')->with('format')->will(self::returnSelf());

        $expectedModifiedArguments = [
            'module' => 'the/path',
            'moduleArguments' => ['arguments', '@action' => 'action']
        ];

        $this->uriBuilder->expects(self::once())->method('uriFor')->with('index', $expectedModifiedArguments)->willReturn('expectedUri');

        // fallback for the method chaining of the URI builder
        $this->uriBuilder->expects(self::any())->method($this->anything())->willReturn($this->uriBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => 'the/path',
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
}
