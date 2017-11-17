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
use Neos\Neos\Http\ContentDimensionLinking\SubdomainDimensionPresetLinkProcessor;
use Neos\Neos\Http\ContentDimensionResolutionMode;

/**
 * Test case for the SubdomainDimensionPresetLinkProcessor
 */
class SubdomainDimensionPresetLinkProcessorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'resolution' => [
            'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN,
        ],
        'defaultPreset' => 'en',
        'allowEmptyValue' => true,
        'presets' => [
            'en' => [
                'values' => ['en'],
                'resolutionValue' => ''
            ],
            'de' => [
                'values' => ['de'],
                'resolutionValue' => 'de'
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
    public function processDimensionBaseUriAddsSubdomainIfNecessaryAndNoneYetPresent()
    {
        $linkProcessor = new SubdomainDimensionPresetLinkProcessor();
        $baseUri = new Http\Uri('https://domain.com');
        $linkProcessor->processDimensionBaseUri(
            $baseUri,
            'language',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['presets']['fr']
        );

        $this->assertSame(
            'https://fr.domain.com',
            (string)$baseUri
        );
    }

    /**
     * @test
     */
    public function processDimensionBaseUriReplacesSubdomainIfDifferentOnePresent()
    {
        $linkProcessor = new SubdomainDimensionPresetLinkProcessor();
        $baseUri = new Http\Uri('https://de.domain.com');
        $linkProcessor->processDimensionBaseUri(
            $baseUri,
            'language',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['presets']['fr']
        );

        $this->assertSame(
            'https://fr.domain.com',
            (string)$baseUri
        );
    }

    /**
     * @test
     */
    public function processDimensionBaseUriKeepsSubdomainIfAlreadyPresent()
    {
        $linkProcessor = new SubdomainDimensionPresetLinkProcessor();
        $baseUri = new Http\Uri('https://fr.domain.com');
        $linkProcessor->processDimensionBaseUri(
            $baseUri,
            'language',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['presets']['fr']
        );

        $this->assertSame(
            'https://fr.domain.com',
            (string)$baseUri
        );
    }

    /**
     * @test
     */
    public function processDimensionBaseUriRemovesSubdomainIfPresentButNotNeeded()
    {
        $linkProcessor = new SubdomainDimensionPresetLinkProcessor();
        $baseUri = new Http\Uri('https://fr.domain.com');
        $linkProcessor->processDimensionBaseUri(
            $baseUri,
            'language',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['presets']['en']
        );

        $this->assertSame(
            'https://domain.com',
            (string)$baseUri
        );
    }
}
