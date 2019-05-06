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
use Neos\Neos\Http\ContentDimensionDetection;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Neos\Tests\Unit\Http\ContentDimensionDetection\Fixtures\InvalidDummyDimensionPresetDetector;
use Neos\Neos\Tests\Unit\Http\ContentDimensionDetection\Fixtures\ValidDummyDimensionPresetDetector;

/**
 * Test case for the DimensionPresetDetectorResolver
 */
class DimensionPresetDetectorResolverTest extends UnitTestCase
{
    public function resolutionModeProvider(): array
    {
        return [
            [ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN, ContentDimensionDetection\SubdomainDimensionPresetDetector::class],
            [ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN, ContentDimensionDetection\TopLevelDomainDimensionPresetDetector::class],
            [ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT, ContentDimensionDetection\UriPathSegmentDimensionPresetDetector::class],
            [null, ContentDimensionDetection\UriPathSegmentDimensionPresetDetector::class]
        ];
    }

    /**
     * @test
     * @dataProvider resolutionModeProvider
     * @param string|null $resolutionMode
     * @param string $expectedDetectorClassName
     */
    public function resolveDimensionPresetDetectorReturnsSubdomainDetectorForMatchingResolutionMode(?string $resolutionMode, string $expectedDetectorClassName)
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();

        $resolutionOptions = $resolutionMode
            ? ['resolution' => ['mode' => $resolutionMode]]
            : [];
        $detector = $resolver->resolveDimensionPresetDetector('dimensionName', $resolutionOptions);

        $this->assertSame($expectedDetectorClassName, get_class($detector));
    }

    /**
     * @test
     */
    public function resolveDimensionPresetDetectorReturnsConfiguredDetectorIfImplementationClassExistsAndImplementsTheDetectorInterface()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();

        $detector = $resolver->resolveDimensionPresetDetector('dimensionName', [
            'resolution' => ['mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN],
            'detectionComponent' => [
                'implementationClassName' => ValidDummyDimensionPresetDetector::class
            ]
        ]);

        $this->assertSame(ValidDummyDimensionPresetDetector::class, get_class($detector));
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\ContentDimensionDetection\DimensionPresetDetectorIsInvalid
     */
    public function resolveDimensionPresetDetectorThrowsExceptionWithNotExistingDetectorImplementationClassConfigured()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();

        $resolver->resolveDimensionPresetDetector('dimensionName', [
            'detectionComponent' => [
                'implementationClassName' => 'Neos\Neos\Http\ContentDimensionDetection\NonExistingImplementation'
            ]
        ]);
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\ContentDimensionDetection\DimensionPresetDetectorIsInvalid
     */
    public function resolveDimensionPresetDetectorThrowsExceptionWithImplementationClassNotImplementingTheDetectorInterfaceConfigured()
    {
        $resolver = new ContentDimensionDetection\DimensionPresetDetectorResolver();

        $resolver->resolveDimensionPresetDetector('dimensionName', [
            'detectionComponent' => [
                'implementationClassName' => InvalidDummyDimensionPresetDetector::class
            ]
        ]);
    }
}
