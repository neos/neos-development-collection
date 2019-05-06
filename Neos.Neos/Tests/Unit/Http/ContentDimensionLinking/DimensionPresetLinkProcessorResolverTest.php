<?php
namespace Neos\Neos\Tests\Unit\Http\ContentDimensionLinking;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\ContentDimensionLinking;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Neos\Tests\Unit\Http\ContentDimensionLinking\Fixtures\InvalidDummyDimensionPresetLinkProcessor;
use Neos\Neos\Tests\Unit\Http\ContentDimensionLinking\Fixtures\ValidDummyDimensionPresetLinkProcessor;

/**
 * Test cases for the DimensionPresetLinkProcessorResolver
 */
class DimensionPresetLinkProcessorResolverTest extends UnitTestCase
{
    public function resolutionModeProvider(): array
    {
        return [
            [ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN, ContentDimensionLinking\SubdomainDimensionPresetLinkProcessor::class],
            [ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN, ContentDimensionLinking\TopLevelDomainDimensionPresetLinkProcessor::class],
            [ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT, ContentDimensionLinking\UriPathSegmentDimensionPresetLinkProcessor::class],
            [null, ContentDimensionLinking\UriPathSegmentDimensionPresetLinkProcessor::class]
        ];
    }

    /**
     * @test
     * @dataProvider resolutionModeProvider
     * @param string|null $resolutionMode
     * @param string $expectedLinkProcessorClassName
     */
    public function resolveDimensionPresetLinkProcessorReturnsSubdomainLinkProcessorForMatchingResolutionMode(?string $resolutionMode, string $expectedLinkProcessorClassName)
    {
        $resolver = new ContentDimensionLinking\DimensionPresetLinkProcessorResolver();

        $resolutionOptions = $resolutionMode
            ? ['resolution' => ['mode' => $resolutionMode]]
            : [];

        $this->assertSame(
            $expectedLinkProcessorClassName,
            get_class($resolver->resolveDimensionPresetLinkProcessor('dimensionName', $resolutionOptions))
        );
    }

    /**
     * @test
     */
    public function resolveDimensionPresetLinkProcessorReturnsConfiguredLinkProcessorIfImplementationClassExistsAndImplementsTheLinkProcessorInterface()
    {
        $resolver = new ContentDimensionLinking\DimensionPresetLinkProcessorResolver();

        $linkProcessor = $resolver->resolveDimensionPresetLinkProcessor('dimensionName', [
            'resolution' => ['mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN],
            'linkProcessorComponent' => [
                'implementationClassName' => ValidDummyDimensionPresetLinkProcessor::class
            ]
        ]);

        $this->assertSame(ValidDummyDimensionPresetLinkProcessor::class, get_class($linkProcessor));
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\ContentDimensionLinking\DimensionPresetLinkProcessorIsInvalid
     */
    public function resolveDimensionPresetLinkProcessorThrowsExceptionWithNotExistingLinkProcessorImplementationClassConfigured()
    {
        $resolver = new ContentDimensionLinking\DimensionPresetLinkProcessorResolver();

        $resolver->resolveDimensionPresetLinkProcessor('dimensionName', [
            'linkProcessorComponent' => [
                'implementationClassName' => 'Neos\Neos\Http\ContentDimensionLinking\NonExistingImplementation'
            ]
        ]);
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\ContentDimensionLinking\DimensionPresetLinkProcessorIsInvalid
     */
    public function resolveDimensionPresetLinkProcessorThrowsExceptionWithImplementationClassNotImplementingTheLinkProcessorInterfaceConfigured()
    {
        $resolver = new ContentDimensionLinking\DimensionPresetLinkProcessorResolver();

        $resolver->resolveDimensionPresetLinkProcessor('dimensionName', [
            'linkProcessorComponent' => [
                'implementationClassName' => InvalidDummyDimensionPresetLinkProcessor::class
            ]
        ]);
    }
}
