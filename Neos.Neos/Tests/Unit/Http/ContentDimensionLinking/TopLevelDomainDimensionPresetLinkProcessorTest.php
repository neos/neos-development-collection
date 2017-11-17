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
use Neos\Neos\Http\ContentDimensionLinking\TopLevelDomainDimensionPresetLinkProcessor;
use Neos\Neos\Http\ContentDimensionResolutionMode;

/**
 * Test case for the SubdomainDimensionPresetLinkProcessor
 */
class TopLevelDomainDimensionPresetLinkProcessorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'resolution' => [
            'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN,
        ],
        'defaultPreset' => 'en',
        'allowEmptyValue' => true,
        'presets' => [
            'en' => [
                'values' => ['en'],
                'resolutionValue' => 'com'
            ],
            'fr' => [
                'values' => ['fr'],
                'resolutionValue' => 'fr'
            ]
        ]
    ];


    /**
     * @test
     */
    public function processDimensionBaseUriReplacesTopLevelDomainIfDifferentOnePresent()
    {
        $linkProcessor = new TopLevelDomainDimensionPresetLinkProcessor();
        $baseUri = new Http\Uri('https://domain.com');
        $linkProcessor->processDimensionBaseUri(
            $baseUri,
            'language',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['presets']['fr']
        );

        $this->assertSame(
            'https://domain.fr',
            (string)$baseUri
        );
    }

    /**
     * @test
     */
    public function processDimensionBaseUriKeepsSubdomainIfAlreadyPresent()
    {
        $linkProcessor = new TopLevelDomainDimensionPresetLinkProcessor();
        $baseUri = new Http\Uri('https://domain.fr');
        $linkProcessor->processDimensionBaseUri(
            $baseUri,
            'language',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['presets']['fr']
        );

        $this->assertSame(
            'https://domain.fr',
            (string)$baseUri
        );
    }
}
