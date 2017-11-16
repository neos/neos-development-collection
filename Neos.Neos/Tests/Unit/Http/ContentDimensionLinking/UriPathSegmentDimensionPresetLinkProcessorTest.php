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
use Neos\Flow\Http;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\ContentDimensionLinking\UriPathSegmentDimensionPresetLinkProcessor;
use Neos\Neos\Http\ContentDimensionResolutionMode;

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
                    'offset' => 1,
                    'delimiter' => '-'
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
    public function processDimensionBaseUriAddsFirstPathSegment()
    {
        $linkProcessor = new UriPathSegmentDimensionPresetLinkProcessor();
        $baseUri = new Http\Uri('https://domain.com');

        $linkProcessor->processDimensionBaseUri(
            $baseUri,
            'language',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['language']['presets']['en']
        );

        $this->assertSame(
            'https://domain.com/en',
            (string)$baseUri
        );
    }

    /**
     * @test
     */
    public function processDimensionBaseUriAddsSecondPathSegment()
    {
        $linkProcessor = new UriPathSegmentDimensionPresetLinkProcessor();
        $baseUri = new Http\Uri('https://domain.com/en');

        $linkProcessor->processDimensionBaseUri(
            $baseUri,
            'market',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['market']['presets']['GB']
        );

        $this->assertSame(
            'https://domain.com/en-GB',
            (string)$baseUri
        );
    }
}
