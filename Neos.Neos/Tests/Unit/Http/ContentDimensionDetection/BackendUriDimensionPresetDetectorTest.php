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

    public function uriSegmentBasedUriAndPresetProvider(): array
    {
        return [
            ['https://domain.com/@user-me', null],
            ['https://domain.com/@user-me;language=sjn', null],
            ['https://domain.com/@user-me;language=nl,de', 'nl'],
            ['https://domain.com/de/@user-me;language=nl,de', 'nl']
        ];
    }

    /**
     * @test
     * @dataProvider uriSegmentBasedUriAndPresetProvider
     * @param string $rawUri
     * @param string $presetKey
     */
    public function detectPresetDetectsPresetFromBackendUri(string $rawUri, ?string $presetKey): void
    {
        $presetDetector = new ContentDimensionDetection\BackendUriDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri($rawUri));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame($presetKey ? $this->dimensionConfiguration['presets'][$presetKey] : null,
            $presetDetector->detectPreset(
                'language',
                $this->dimensionConfiguration['presets'],
                $componentContext
            )
        );
    }
}
