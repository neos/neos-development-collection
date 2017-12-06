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
 * Test case for the BackendUriDimensionPresetDetector
 */
class BackendUriDimensionPresetDetectorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'resolution' => [
            'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
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
            ],
            'nl' => [
                'values' => ['nl', 'de'],
                'resolutionValue' => 'nl'
            ]
        ]
    ];


    /**
     * @test
     */
    public function detectPresetDetectsPresetFromComponentContextWithBackendUrlContainingSerializedPreset()
    {
        $presetDetector = new ContentDimensionDetection\BackendUriDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/@user-me;language=nl,de'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame($this->dimensionConfiguration['presets']['nl'],
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
    public function detectPresetDetectsNoPresetFromComponentContextWithBackendUrlNotContainingSerializedPreset()
    {
        $presetDetector = new ContentDimensionDetection\BackendUriDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/@user-me'));
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
    public function detectPresetDetectsPresetFromComponentContextWithBackendUrlContainingSerializedPresetDifferentFromTheFrontendUrlPreset()
    {
        $presetDetector = new ContentDimensionDetection\BackendUriDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/fr_EU/@user-me;language=nl,de'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame($this->dimensionConfiguration['presets']['nl'],
            $presetDetector->detectPreset(
                'language',
                $this->dimensionConfiguration['presets'],
                $componentContext
            )
        );
    }
}
