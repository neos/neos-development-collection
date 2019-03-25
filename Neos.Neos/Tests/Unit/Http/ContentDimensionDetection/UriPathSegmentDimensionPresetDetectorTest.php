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
 * Test case for the UriPathSegmentDimensionPresetDetector
 */
class UriPathSegmentDimensionPresetDetectorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'market' => [
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                'options' => [
                    'offset' => 1
                ]
            ],
            'defaultPreset' => 'GB',
            'presets' => [
                'GB' => [
                    'values' => ['GB'],
                    'resolutionValue' => 'GB'
                ]
            ]
        ],
        'language' => [
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                'options' => [
                    'offset' => 0
                ]
            ],
            'defaultPreset' => 'en',
            'presets' => [
                'en' => [
                    'values' => ['en'],
                    'resolutionValue' => 'en'
                ]
            ]
        ]
    ];

    public function uriSegmentBasedUriAndPresetProvider(): array
    {
        return [
            ['https://domain.com/en-GB', '-', 'GB', 'en'],
            ['https://domain.com/en_GB', '_', 'GB', 'en'],
            ['https://domain.com/en-GB@user-me', '-', 'GB', 'en'],
            ['https://domain.com/wat', '-', null, null],
            ['https://domain.com', '-', null, null]
        ];
    }

    /**
     * @test
     * @dataProvider uriSegmentBasedUriAndPresetProvider
     * @param string $rawUri
     * @param string $delimiter
     * @param string|null $expectedMarketPresetKey
     * @param string|null $expectedLanguagePresetKey
     */
    public function detectPresetDetectsPresetSerializedInComponentContextsFirstUriPathSegmentPart(string $rawUri, string $delimiter, ?string $expectedMarketPresetKey, ?string $expectedLanguagePresetKey)
    {
        $presetDetector = new ContentDimensionDetection\UriPathSegmentDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri($rawUri));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $marketOptions = $this->dimensionConfiguration['market']['resolution']['options'];
        $marketOptions['delimiter'] = $delimiter;
        $this->assertSame($expectedMarketPresetKey ? $this->dimensionConfiguration['market']['presets'][$expectedMarketPresetKey] : null,
            $presetDetector->detectPreset(
                'market',
                $this->dimensionConfiguration['market']['presets'],
                $componentContext,
                $marketOptions
            )
        );

        $languageOptions = $this->dimensionConfiguration['language']['resolution']['options'];
        $languageOptions['delimiter'] = $delimiter;

        $this->assertSame($expectedLanguagePresetKey ? $this->dimensionConfiguration['language']['presets'][$expectedLanguagePresetKey] : null,
            $presetDetector->detectPreset(
                'language',
                $this->dimensionConfiguration['language']['presets'],
                $componentContext,
                $languageOptions
            )
        );
    }
}
