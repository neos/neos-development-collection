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
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\ContentDimensionLinking\UriPathSegmentDimensionPresetLinkProcessor;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Utility\ObjectAccess;

/**
 * Test case for the SubdomainDimensionPresetLinkProcessor
 */
class UriPathSegmentDimensionPresetLinkProcessorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'market' => [
            'defaultPreset' => 'GB',
            'presets' => [
                'GB' => [
                    'values' => ['GB'],
                    'resolutionValue' => 'GB'
                ]
            ],
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                'options' => [
                    'offset' => 1
                ]
            ]
        ],
        'language' => [
            'defaultPreset' => 'en',
            'presets' => [
                'en' => [
                    'values' => ['en'],
                    'resolutionValue' => 'en'
                ]
            ],
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                'options' => [
                    'offset' => 0
                ]
            ]
        ],
    ];

    /**
     * @test
     */
    public function processUriConstraintsAddsFirstPathPrefix()
    {
        $linkProcessor = new UriPathSegmentDimensionPresetLinkProcessor();
        $uriConstraints = UriConstraints::create();

        $options = $this->dimensionConfiguration['language']['resolution']['options'];
        $options['delimiter'] = '-';

        $processedUriConstraints = $linkProcessor->processUriConstraints(
            $uriConstraints,
            'language',
            $this->dimensionConfiguration['language'],
            $this->dimensionConfiguration['language']['presets']['en'],
            $options
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            'en',
            $constraints['pathPrefix']
        );
    }

    /**
     * @test
     */
    public function processUriConstraintsAddsSecondPathPrefixWithGivenDelimiter()
    {
        $linkProcessor = new UriPathSegmentDimensionPresetLinkProcessor();
        $uriConstraints = UriConstraints::create();

        $options = $this->dimensionConfiguration['market']['resolution']['options'];
        $options['delimiter'] = '-';

        $processedUriConstraints = $linkProcessor->processUriConstraints(
            $uriConstraints,
            'language',
            $this->dimensionConfiguration['market'],
            $this->dimensionConfiguration['market']['presets']['GB'],
            $options
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            '-GB',
            $constraints['pathPrefix']
        );
    }
}
