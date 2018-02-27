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
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\BasicContentDimensionResolutionMode;
use Neos\Neos\Http\ContentDimensionDetection;

/**
 * Test cases for the ContentDimensionValueDetectorResolver
 */
class ContentDimensionValueDetectorResolverTest extends UnitTestCase
{
    /**
     * @test
     * @throws ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     */
    public function resolveContentDimensionValueDetectorReturnsHostPrefixDetectorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionDetection\ContentDimensionValueDetectorResolver();

        $defaultValue = new Dimension\ContentDimensionValue('default');
        $contentDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimensionName'),
            [
                $defaultValue
            ],
            $defaultValue,
            [],
            [
                'resolution' => ['mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTPREFIX],
            ]
        );

        $this->assertSame(
            ContentDimensionDetection\HostPrefixContentDimensionValueDetector::class,
            get_class($resolver->resolveContentDimensionValueDetector($contentDimension))
        );
    }

    /**
     * @test
     * @throws ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     */
    public function resolveContentDimensionValueDetectorReturnsHostSuffixDetectorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionDetection\ContentDimensionValueDetectorResolver();

        $defaultValue = new Dimension\ContentDimensionValue('default');
        $contentDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimensionName'),
            [
                $defaultValue
            ],
            $defaultValue,
            [],
            [
                'resolution' => ['mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTSUFFIX],
            ]
        );

        $this->assertSame(
            ContentDimensionDetection\HostSuffixContentDimensionValueDetector::class,
            get_class($resolver->resolveContentDimensionValueDetector($contentDimension))
        );
    }

    /**
     * @test
     * @throws ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     */
    public function resolveContentDimensionValueDetectorReturnsUriPathSegmentDetectorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionDetection\ContentDimensionValueDetectorResolver();

        $defaultValue = new Dimension\ContentDimensionValue('default');
        $contentDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimensionName'),
            [
                $defaultValue
            ],
            $defaultValue,
            [],
            [
                'resolution' => ['mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT],
            ]
        );

        $this->assertSame(
            ContentDimensionDetection\UriPathSegmentContentDimensionValueDetector::class,
            get_class($resolver->resolveContentDimensionValueDetector($contentDimension))
        );
    }

    /**
     * @test
     * @throws ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     */
    public function resolveDimensionPresetDetectorReturnsUriPathSegmentDetectorIfNothingWasConfigured()
    {
        $resolver = new ContentDimensionDetection\ContentDimensionValueDetectorResolver();

        $defaultValue = new Dimension\ContentDimensionValue('default');
        $contentDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimensionName'),
            [
                $defaultValue
            ],
            $defaultValue
        );

        $this->assertSame(
            ContentDimensionDetection\UriPathSegmentContentDimensionValueDetector::class,
            get_class($resolver->resolveContentDimensionValueDetector($contentDimension))
        );
    }

    /**
     * @test
     * @throws ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     */
    public function resolveDimensionPresetDetectorReturnsConfiguredDetectorIfImplementationClassExistsAndImplementsTheDetectorInterface()
    {
        $resolver = new ContentDimensionDetection\ContentDimensionValueDetectorResolver();

        $defaultValue = new Dimension\ContentDimensionValue('default');
        $contentDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimensionName'),
            [
                $defaultValue
            ],
            $defaultValue,
            [],
            [
                'resolution' => [
                    'detectionComponent' => [
                        'implementationClassName' => Fixtures\ValidDummyContentDimensionValueDetector::class
                    ]
                ]
            ]
        );

        $this->assertSame(
            Fixtures\ValidDummyContentDimensionValueDetector::class,
            get_class($resolver->resolveContentDimensionValueDetector($contentDimension))
        );
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     * @throws ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     */
    public function resolveDimensionPresetDetectorThrowsExceptionWithNotExistingDetectorImplementationClassConfigured()
    {
        $resolver = new ContentDimensionDetection\ContentDimensionValueDetectorResolver();

        $defaultValue = new Dimension\ContentDimensionValue('default');
        $contentDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimensionName'),
            [
                $defaultValue
            ],
            $defaultValue,
            [],
            [
                'resolution' => [
                    'detectionComponent' => [
                        'implementationClassName' => 'Neos\Neos\Http\ContentDimensionDetection\NonExistingImplementation'
                    ]
                ]
            ]
        );

        $resolver->resolveContentDimensionValueDetector($contentDimension);
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     * @throws ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     */
    public function resolveDimensionPresetDetectorThrowsExceptionWithImplementationClassNotImplementingTheDetectorInterfaceConfigured()
    {
        $resolver = new ContentDimensionDetection\ContentDimensionValueDetectorResolver();

        $defaultValue = new Dimension\ContentDimensionValue('default');
        $contentDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimensionName'),
            [
                $defaultValue
            ],
            $defaultValue,
            [],
            [
                'resolution' => [
                    'detectionComponent' => [
                        'implementationClassName' => Fixtures\InvalidDummyContentDimensionValueDetector::class
                    ]
                ]
            ]
        );

        $resolver->resolveContentDimensionValueDetector($contentDimension);
    }
}
