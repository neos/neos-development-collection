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
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\BasicContentDimensionResolutionMode;
use Neos\Neos\Http\ContentDimensionLinking;

/**
 * Test cases for the ContentDimensionValueUriProcessorResolver
 */
class ContentDimensionValueUriProcessorResolverTest extends UnitTestCase
{
    /**
     * @test
     * @throws \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveContentDimensionValueUriProcessorReturnsHostPrefixContentDimensionValueUriProcessorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionLinking\ContentDimensionValueUriProcessorResolver();

        $contentDimension = $this->createDummyDimension([
            'resolution' => ['mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTPREFIX],
        ]);
        $linkProcessor = $resolver->resolveContentDimensionValueUriProcessor($contentDimension);

        $this->assertSame(ContentDimensionLinking\HostPrefixContentDimensionValueUriProcessor::class, get_class($linkProcessor));
    }

    /**
     * @test
     * @throws \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveContentDimensionValueUriProcessorReturnsHostSuffixContentDimensionValueUriProcessorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionLinking\ContentDimensionValueUriProcessorResolver();

        $contentDimension = $this->createDummyDimension([
            'resolution' => ['mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTSUFFIX],
        ]);
        $linkProcessor = $resolver->resolveContentDimensionValueUriProcessor($contentDimension);

        $this->assertSame(ContentDimensionLinking\HostSuffixContentDimensionValueUriProcessor::class, get_class($linkProcessor));
    }

    /**
     * @test
     * @throws \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveContentDimensionValueUriProcessorReturnsUriPathSegmentContentDimensionValueUriProcessorForMatchingResolutionMode()
    {
        $resolver = new ContentDimensionLinking\ContentDimensionValueUriProcessorResolver();

        $contentDimension = $this->createDummyDimension([
            'resolution' => ['mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT],
        ]);
        $linkProcessor = $resolver->resolveContentDimensionValueUriProcessor($contentDimension);

        $this->assertSame(ContentDimensionLinking\UriPathSegmentContentDimensionValueUriProcessor::class, get_class($linkProcessor));
    }

    /**
     * @test
     * @throws \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveContentDimensionValueUriProcessorReturnsNullContentDimensionValueUriProcessorIfNothingWasConfigured()
    {
        $resolver = new ContentDimensionLinking\ContentDimensionValueUriProcessorResolver();

        $contentDimension = $this->createDummyDimension([]);
        $linkProcessor = $resolver->resolveContentDimensionValueUriProcessor($contentDimension);

        $this->assertSame(ContentDimensionLinking\NullContentDimensionValueUriProcessor::class, get_class($linkProcessor));
    }

    /**
     * @test
     * @throws \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveContentDimensionValueUriProcessorReturnsConfiguredUriProcessorIfImplementationClassExistsAndImplementsTheLinkProcessorInterface()
    {
        $resolver = new ContentDimensionLinking\ContentDimensionValueUriProcessorResolver();

        $contentDimension = $this->createDummyDimension([
            'resolution' => [
                'linkProcessorComponent' => [
                    'implementationClassName' => Fixtures\ValidDummyDimensionValueUriProcessor::class
                ]
            ]
        ]);
        $linkProcessor = $resolver->resolveContentDimensionValueUriProcessor($contentDimension);

        $this->assertSame(Fixtures\ValidDummyDimensionValueUriProcessor::class, get_class($linkProcessor));
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     * @throws \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveContentDimensionValueUriProcessorThrowsExceptionWithNotExistingLinkProcessorImplementationClassConfigured()
    {
        $resolver = new ContentDimensionLinking\ContentDimensionValueUriProcessorResolver();

        $contentDimension = $this->createDummyDimension([
            'resolution' => [
                'linkProcessorComponent' => [
                    'implementationClassName' => 'Neos\Neos\Http\ContentDimensionLinking\NonExistingImplementation'
                ]
            ]
        ]);
        $resolver->resolveContentDimensionValueUriProcessor($contentDimension);
    }

    /**
     * @test
     * @expectedException \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     * @throws \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveContentDimensionValueUriProcessorThrowsExceptionWithImplementationClassNotImplementingTheLinkProcessorInterfaceConfigured()
    {
        $resolver = new ContentDimensionLinking\ContentDimensionValueUriProcessorResolver();

        $contentDimension = $this->createDummyDimension([
            'resolution' => [
                'linkProcessorComponent' => [
                    'implementationClassName' => Fixtures\InvalidDummyDimensionValueUriProcessor::class
                ]
            ]
        ]);
        $resolver->resolveContentDimensionValueUriProcessor($contentDimension);
    }

    /**
     * @param array $additionalConfiguration
     * @return Dimension\ContentDimension
     */
    protected function createDummyDimension(array $additionalConfiguration): Dimension\ContentDimension
    {
        $defaultValue = new Dimension\ContentDimensionValue('default');

        return new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimensionName'),
            [
                $defaultValue
            ],
            $defaultValue,
            [],
            $additionalConfiguration
        );
    }
}
