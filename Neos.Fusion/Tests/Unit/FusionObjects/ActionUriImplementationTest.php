<?php
namespace Neos\Fusion\Tests\Unit\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\ActionUriImplementation;

/**
 * Testcase for the Fusion ActionUri object
 */
class ActionUriImplementationTest extends UnitTestCase
{
    /**
     * @var ActionUriImplementation
     */
    protected $mockActionUriImplementation;

    /**
     * @var UriBuilder
     */
    protected $mockUriBuilder;

    /**
     * @var Runtime
     */
    protected $mockRuntime;

    public function setUp(): void
    {
        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $this->mockUriBuilder = $this->getMockBuilder(UriBuilder::class)->disableOriginalConstructor()->getMock();

        $methodsToMock = [
            'getRequest',
            'getPackage',
            'getSubpackage',
            'getController',
            'getAction',
            'getRoutingArguments',
            'getArguments',
            'getFormat',
            'getSection',
            'getAdditionalParams',
            'getQueryParameters',
            'isAbsolute',
            'getArgumentsToBeExcludedFromQueryString',
            'isAddQueryString',
            'createUriBuilder'
        ];

        $this->mockActionUriImplementation = $this->getMockBuilder(ActionUriImplementation::class)->disableOriginalConstructor()->onlyMethods($methodsToMock)->getMock();
        $this->mockActionUriImplementation->expects($this->once())->method('createUriBuilder')->willReturn($this->mockUriBuilder);
        $this->inject($this->mockActionUriImplementation, 'runtime', $this->mockRuntime);
        $this->inject($this->mockActionUriImplementation, 'path', '/example/path');
    }

    /**
     * @return void
     * @test
     */
    public function actionIsPassedToTheUriBuilder()
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockUriBuilder->expects($this->once())->method('uriFor')->with('hello', [], null, null, null)->willReturn("http://example.com");
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function formatIsPassedToTheUriBuilder()
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('getFormat')->willReturn('square');
        $this->mockUriBuilder->expects($this->once())->method('setFormat')->with('square');
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function additionalParamsArePassedToTheUriBuilder()
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('getAdditionalParams')->willReturn(['nudel' => 'suppe']);
        $this->mockUriBuilder->expects($this->once())->method('setArguments')->with(['nudel' => 'suppe']);
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function argumentsToBeExcludedFromQueryStringArePassedToTheUriBuilder()
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('getArgumentsToBeExcludedFromQueryString')->willReturn(['nudel', 'suppe']);
        $this->mockUriBuilder->expects($this->once())->method('setArgumentsToBeExcludedFromQueryString')->with(['nudel', 'suppe']);
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function absoluteIsPassedToTheUriBuilder()
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('isAbsolute')->willReturn(true);
        $this->mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(true);
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function sectionIsPassedToTheUriBuilder()
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('getSection')->willReturn('something');
        $this->mockUriBuilder->expects($this->once())->method('setSection')->with('something');
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function addQueryStringIsPassedToTheUriBuilder()
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('isAddQueryString')->willReturn(true);
        $this->mockUriBuilder->expects($this->once())->method('setAddQueryString')->with(true);
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function actionPackageAndArgumentsArePassedToUriBuilder()
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('getArguments')->willReturn(['test' => 123]);
        $this->mockActionUriImplementation->expects($this->once())->method('getRoutingArguments')->willReturn([]);
        $this->mockActionUriImplementation->expects($this->once())->method('getController')->willReturn('Special');
        $this->mockActionUriImplementation->expects($this->once())->method('getPackage')->willReturn('Vendor.Package');
        $this->mockActionUriImplementation->expects($this->once())->method('getSubpackage')->willReturn('Stuff');

        $this->mockUriBuilder->expects($this->once())->method('uriFor')->with('hello', ['test' => 123], 'Special', 'Vendor.Package', 'Stuff')->willReturn("http://example.com");
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function actionPackageAndRoutingArgumentsArePassedToUriBuilder()
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('getArguments')->willReturn([]);
        $this->mockActionUriImplementation->expects($this->once())->method('getRoutingArguments')->willReturn(['test' => 123]);
        $this->mockActionUriImplementation->expects($this->once())->method('getController')->willReturn('Special');
        $this->mockActionUriImplementation->expects($this->once())->method('getPackage')->willReturn('Vendor.Package');
        $this->mockActionUriImplementation->expects($this->once())->method('getSubpackage')->willReturn('Stuff');

        $this->mockUriBuilder->expects($this->once())->method('uriFor')->with('hello', ['test' => 123], 'Special', 'Vendor.Package', 'Stuff')->willReturn("http://example.com");
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function actionUriUsesArguments(): void
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('getArguments')->willReturn(['foo' => 'bar']);
        $this->mockActionUriImplementation->expects($this->once())->method('getRoutingArguments')->willReturn([]) ;
        $this->mockUriBuilder->expects($this->once())->method('uriFor')->with('hello', ['foo' => 'bar'], null, null, null)->willReturn("http://hostname");
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function actionUriUsesRoutingArguments(): void
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('getArguments')->willReturn([]);
        $this->mockActionUriImplementation->expects($this->once())->method('getRoutingArguments')->willReturn(['foo' => 'bar']) ;
        $this->mockUriBuilder->expects($this->once())->method('uriFor')->with('hello', ['foo' => 'bar'], null, null, null)->willReturn("http://hostname");
        $this->mockActionUriImplementation->evaluate();
    }

    /**
     * @return void
     * @test
     */
    public function actionUriThrowsExceptionIfRoutingArgumentAreUsedTogetherWithArguments(): void
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getArguments')->willReturn(['bar' => 'baz']);
        $this->mockActionUriImplementation->expects($this->once())->method('getRoutingArguments')->willReturn(['foo' => 'bar']) ;
        $this->mockUriBuilder->expects($this->never())->method('uriFor');
        $this->mockRuntime->expects($this->once())->method('handleRenderingException');
        $this->mockActionUriImplementation->evaluate();
    }

    public function queryParameterAppendingDataProvider(): array
    {
        return [
            ['https://example.com', ['foo' => 'bar'], 'https://example.com?foo=bar'],
            ['https://example.com?foo=bar', ['bar' => 'baz'], 'https://example.com?foo=bar&bar=baz'],
            ['https://example.com?foo=bar', ['foo' => 'bam'], 'https://example.com?foo=bam'],
            ['https://example.com', ['foo' => ['bar' => 'baz']], 'https://example.com?foo%5Bbar%5D=baz'],
            ['https://example.com?foo=bar', ['foo' => ['bar' => 'baz']], 'https://example.com?foo%5Bbar%5D=baz'],
            ['https://example.com?foo[bar]=baz', ['foo' => ['blah' => 'blubb']], 'https://example.com?foo%5Bbar%5D=baz&foo%5Bblah%5D=blubb']
        ];
    }

    /**
     * @return void
     * @dataProvider queryParameterAppendingDataProvider
     * @test
     */
    public function actionUriAppendsQueryParametersToUri($uriFromLinking, $queryParameters, $expectedFinalUri): void
    {
        $this->mockActionUriImplementation->expects($this->once())->method('getAction')->willReturn('hello');
        $this->mockActionUriImplementation->expects($this->once())->method('getQueryParameters')->willReturn($queryParameters);
        $this->mockUriBuilder->expects($this->once())->method('uriFor')->with('hello', [], null, null, null)->willReturn($uriFromLinking);
        $actualResult = $this->mockActionUriImplementation->evaluate();
        $this->assertEquals($expectedFinalUri, $actualResult);
    }
}
