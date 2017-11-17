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
use Neos\Neos\Http\ContentDimensionDetection;
use Neos\Neos\Http\ContentDimensionResolutionMode;

/**
 * Test case for the SubdomainDimensionPresetDetector
 */
class SubdomainDimensionPresetDetectorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'resolution' => [
            'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN,
        ],
        'defaultPreset' => 'en',
        'presets' => [
            'en' => [
                'values' => ['en'],
                'resolutionValue' => ''
            ],
            'de' => [
                'values' => ['de'],
                'resolutionValue' => 'de'
            ]
        ]
    ];


    /**
     * @test
     */
    public function detectPresetDetectsPresetFromComponentContextWithMatchingSubdomain()
    {
        $presetDetector = new ContentDimensionDetection\SubdomainDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://de.domain.com'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame($this->dimensionConfiguration['presets']['de'],
            $presetDetector->detectPreset(
                'language',
                $this->dimensionConfiguration['presets'],
                $componentContext
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDetectsNoPresetFromComponentContextWithoutSubdomain()
    {
        $presetDetector = new ContentDimensionDetection\SubdomainDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame(null,
            $presetDetector->detectPreset(
                'language',
                $this->dimensionConfiguration['presets'],
                $componentContext
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDetectsNoPresetFromComponentContextWithNotMatchingSubdomain()
    {
        $presetDetector = new ContentDimensionDetection\SubdomainDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://www.domain.com'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame(null,
            $presetDetector->detectPreset(
                'language',
                $this->dimensionConfiguration['presets'],
                $componentContext
            )
        );
    }
}
