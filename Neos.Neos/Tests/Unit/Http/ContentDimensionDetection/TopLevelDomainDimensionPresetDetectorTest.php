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


    /**
     * @test
     */
    public function detectPresetDetectsPresetFromComponentContextWithMatchingTopLevelDomain()
    {
        $presetDetector = new ContentDimensionDetection\TopLevelDomainDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.de'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame($this->dimensionConfiguration['presets']['DE'],
            $presetDetector->detectPreset(
                'market',
                $this->dimensionConfiguration['presets'],
                $componentContext
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDetectsPresetFromComponentContextWithMatchingCompositeDomain()
    {
        $presetDetector = new ContentDimensionDetection\TopLevelDomainDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.co.uk'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame($this->dimensionConfiguration['presets']['GB'],
            $presetDetector->detectPreset(
                'market',
                $this->dimensionConfiguration['presets'],
                $componentContext
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDetectsNoPresetFromComponentContextWithNotMatchingTopLevelDomain()
    {
        $presetDetector = new ContentDimensionDetection\TopLevelDomainDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.fr'));
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
