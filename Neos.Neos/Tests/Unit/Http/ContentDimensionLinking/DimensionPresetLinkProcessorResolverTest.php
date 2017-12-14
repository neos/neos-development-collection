<?php
namespace Neos\Neos\Tests\Unit\Http\ContentDimensionDetection;

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
 * Test case for the DetectContentSubgraphComponent
 */
class DimensionPresetLinkProcessorResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveDimensionPresetLinkProcessorReturnsSubdomainLinkProcessorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionLinking\DimensionPresetLinkProcessorResolver();

        $linkProcessor = $resolver->resolveDimensionPresetLinkProcessor('dimensionName', [
            'resolution' => ['mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN],
        ]);

        $this->assertSame(ContentDimensionLinking\SubdomainDimensionPresetLinkProcessor::class, get_class($linkProcessor));
    }

    /**
     * @test
     */
    public function resolveDimensionPresetLinkProcessorReturnsTopLevelDomainLinkProcessorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionLinking\DimensionPresetLinkProcessorResolver();

        $linkProcessor = $resolver->resolveDimensionPresetLinkProcessor('dimensionName', [
            'resolution' => ['mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN],
        ]);

        $this->assertSame(ContentDimensionLinking\TopLevelDomainDimensionPresetLinkProcessor::class, get_class($linkProcessor));
    }

    /**
     * @test
     */
    public function resolveDimensionPresetLinkProcessorReturnsUriPathSegmentLinkProcessorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionLinking\DimensionPresetLinkProcessorResolver();

        $linkProcessor = $resolver->resolveDimensionPresetLinkProcessor('dimensionName', [
            'resolution' => ['mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT],
        ]);

        $this->assertSame(ContentDimensionLinking\UriPathSegmentDimensionPresetLinkProcessor::class, get_class($linkProcessor));
    }

    /**
     * @test
     */
    public function resolveDimensionPresetLinkProcessorReturnsUriPathSegmentLinkProcessorIfNothingWasConfigured()
    {
        $resolver = new ContentDimensionLinking\DimensionPresetLinkProcessorResolver();

        $linkProcessor = $resolver->resolveDimensionPresetLinkProcessor('dimensionName', []);

        $this->assertSame(ContentDimensionLinking\UriPathSegmentDimensionPresetLinkProcessor::class, get_class($linkProcessor));
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
     * @expectedException \Neos\Neos\Http\Exception\InvalidDimensionPresetLinkProcessorException
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
     * @expectedException \Neos\Neos\Http\Exception\InvalidDimensionPresetLinkProcessorException
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
