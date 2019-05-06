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
 * Test case for the TopLevelDomainDimensionPresetDetector
 */
class TopLevelDomainDimensionPresetDetectorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'resolution' => [
            'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN,
        ],
        'defaultPreset' => 'GB',
        'presets' => [
            'GB' => [
                'values' => ['GB'],
                'resolutionValue' => 'co.uk'
            ],
            'DE' => [
                'values' => ['DE'],
                'resolutionValue' => 'de'
            ]
        ]
    ];

    public function topLevelDomainBasedUriAndPresetProvider(): array
    {
        return [
            ['https://domain.de', 'DE'],
            ['https://domain.co.uk', 'GB'],
            ['https://domain.none', null]
        ];
    }

    /**
     * @test
     * @dataProvider topLevelDomainBasedUriAndPresetProvider
     * @param string $rawUri
     * @param string|null $expectedPresetKey
     */
    public function detectPresetDetectsPresetFromComponentContextWithMatchingTopLevelDomain(string $rawUri, ?string $expectedPresetKey)
    {
        $presetDetector = new ContentDimensionDetection\TopLevelDomainDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri($rawUri));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame($expectedPresetKey ? $this->dimensionConfiguration['presets'][$expectedPresetKey] : null,
            $presetDetector->detectPreset(
                'market',
                $this->dimensionConfiguration['presets'],
                $componentContext
            )
        );
    }
}
