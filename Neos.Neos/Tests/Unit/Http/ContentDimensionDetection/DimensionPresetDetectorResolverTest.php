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

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\ContentDimensionDetection;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Neos\Tests\Unit\Http\ContentDimensionDetection\Fixtures\InvalidDummyDimensionPresetDetector;
use Neos\Neos\Tests\Unit\Http\ContentDimensionDetection\Fixtures\ValidDummyDimensionPresetDetector;

/**
 * Test case for the DetectContentSubgraphComponent
 */
class DimensionPresetDetectorResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveDimensionPresetDetectorReturnsSubdomainDetectorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();
        $resolutionMode = new ContentDimensionResolutionMode(ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN);

        $detector = $resolver->resolveDimensionPresetDetector('dimensionName', [
            'resolutionMode' => (string) $resolutionMode,
        ]);

        $this->assertSame(ContentDimensionDetection\SubdomainDimensionPresetDetector::class, get_class($detector));
    }

    /**
     * @test
     */
    public function resolveDimensionPresetDetectorReturnsTopLevelDomainDetectorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();
        $resolutionMode = new ContentDimensionResolutionMode(ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN);

        $detector = $resolver->resolveDimensionPresetDetector('dimensionName', [
            'resolutionMode' => (string) $resolutionMode,
        ]);

        $this->assertSame(ContentDimensionDetection\TopLevelDomainDimensionPresetDetector::class, get_class($detector));
    }

    /**
     * @test
     */
    public function resolveDimensionPresetDetectorReturnsUriPathSegmentDetectorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();
        $resolutionMode = new ContentDimensionResolutionMode(ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT);

        $detector = $resolver->resolveDimensionPresetDetector('dimensionName', [
            'resolutionMode' => (string) $resolutionMode,
        ]);

        $this->assertSame(ContentDimensionDetection\UriPathSegmentDimensionPresetDetector::class, get_class($detector));
    }

    /**
     * @test
     */
    public function resolveDimensionPresetDetectorReturnsUriPathSegmentDetectorIfNothingWasConfigured()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();

        $detector = $resolver->resolveDimensionPresetDetector('dimensionName', []);

        $this->assertSame(ContentDimensionDetection\UriPathSegmentDimensionPresetDetector::class, get_class($detector));
    }

    /**
     * @test
     */
    public function resolveDimensionPresetDetectorReturnsConfiguredDetectorIfImplementationClassExistsAndImplementsTheDetectorInterface()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();
        $resolutionMode = new ContentDimensionResolutionMode(ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN);

        $detector = $resolver->resolveDimensionPresetDetector('dimensionName', [
            'resolutionMode' => $resolutionMode,
            'detectionComponent' => [
                'implementationClassName' => ValidDummyDimensionPresetDetector::class
            ]
        ]);

        $this->assertSame(ValidDummyDimensionPresetDetector::class, get_class($detector));
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\Exception\InvalidDimensionPresetDetectorException
     */
    public function resolveDimensionPresetDetectorThrowsExceptionWithNotExistingDetectorImplementationClassConfigured()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();

        $resolver->resolveDimensionPresetDetector('dimensionName', [
            'detectionComponent' => [
                'implementationClassName' => InvalidDummyDimensionPresetDetector::class
            ]
        ]);
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\Exception\InvalidDimensionPresetDetectorException
     */
    public function resolveDimensionPresetDetectorThrowsExceptionWithImplementationClassNotImplementingTheDetectorInterfaceConfigured()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();

        $resolver->resolveDimensionPresetDetector('dimensionName', [
            'detectionComponent' => [
                'implementationClassName' => 'Neos\Neos\Http\ContentDimensionDetection\NonExistingImplementation'
            ]
        ]);
    }
}
