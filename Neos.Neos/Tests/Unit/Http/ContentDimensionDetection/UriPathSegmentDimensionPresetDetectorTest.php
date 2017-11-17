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


    /**
     * @test
     */
    public function detectPresetDetectsPresetSerializedInComponentContextsFirstUriPathSegmentPart()
    {
        $presetDetector = new ContentDimensionDetection\UriPathSegmentDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/en-GB'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $options = $this->dimensionConfiguration['language']['resolution']['options'];
        $options['delimiter'] = '-';

        $this->assertSame($this->dimensionConfiguration['language']['presets']['en'],
            $presetDetector->detectPreset(
                'language',
                $this->dimensionConfiguration['language']['presets'],
                $componentContext,
                $options
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDetectsPresetSerializedInComponentContextsSecondUriPathSegmentPart()
    {
        $presetDetector = new ContentDimensionDetection\UriPathSegmentDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/en-GB'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $options = $this->dimensionConfiguration['market']['resolution']['options'];
        $options['delimiter'] = '-';

        $this->assertSame($this->dimensionConfiguration['market']['presets']['GB'],
            $presetDetector->detectPreset(
                'market',
                $this->dimensionConfiguration['market']['presets'],
                $componentContext,
                $options
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDetectsPresetSerializedInComponentContextsBackendUrisFirstUriPathSegmentPart()
    {
        $presetDetector = new ContentDimensionDetection\UriPathSegmentDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/en-GB@user-me'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $options = $this->dimensionConfiguration['language']['resolution']['options'];
        $options['delimiter'] = '-';

        $this->assertSame($this->dimensionConfiguration['language']['presets']['en'],
            $presetDetector->detectPreset(
                'market',
                $this->dimensionConfiguration['language']['presets'],
                $componentContext,
                $options
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDetectsPresetSerializedInComponentContextsBackendUrisSecondUriPathSegmentPart()
    {
        $presetDetector = new ContentDimensionDetection\UriPathSegmentDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/en-GB@user-me'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $options = $this->dimensionConfiguration['market']['resolution']['options'];
        $options['delimiter'] = '-';

        $this->assertSame($this->dimensionConfiguration['market']['presets']['GB'],
            $presetDetector->detectPreset(
                'market',
                $this->dimensionConfiguration['market']['presets'],
                $componentContext,
                $options
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDoesNotDetectPresetFromComponentContextWithoutMatchingSerialization()
    {
        $presetDetector = new ContentDimensionDetection\TopLevelDomainDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/wat'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $options = $this->dimensionConfiguration['language']['resolution']['options'];
        $options['delimiter'] = '-';

        $this->assertSame(null,
            $presetDetector->detectPreset(
                'market',
                $this->dimensionConfiguration['market']['presets'],
                $componentContext,
                $options
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDoesNotDetectPresetFromComponentContextWithoutUriPathSegment()
    {
        $presetDetector = new ContentDimensionDetection\TopLevelDomainDimensionPresetDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $options = $this->dimensionConfiguration['language']['resolution']['options'];
        $options['delimiter'] = '-';

        $this->assertSame(null,
            $presetDetector->detectPreset(
                'market',
                $this->dimensionConfiguration['market']['presets'],
                $componentContext,
                $options
            )
        );
    }
}
