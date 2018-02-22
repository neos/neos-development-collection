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
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Http;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\BasicContentDimensionResolutionMode;
use Neos\Neos\Http\ContentDimensionDetection;

/**
 * Test cases for the UriPathSegmentContentDimensionValueDetector
 */
class UriPathSegmentContentDimensionValueDetectorTest extends UnitTestCase
{
    /**
     * @var array|Dimension\ContentDimension[]
     */
    protected $contentDimensions;


    public function setUp()
    {
        parent::setUp();

        $greatBritain = new Dimension\ContentDimensionValue('GB', new Dimension\ContentDimensionValueSpecializationDepth(0), [], ['resolution' => ['value' => 'GB']]);
        $english = new Dimension\ContentDimensionValue('en', new Dimension\ContentDimensionValueSpecializationDepth(0), [], ['resolution' => ['value' => 'en']]);
        $this->contentDimensions = [
            'market' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('market'),
                [
                    $greatBritain->getValue() => $greatBritain
                ],
                $greatBritain,
                [],
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                        'offset' => 1
                    ]
                ]
            ),
            'language' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('language'),
                [
                    $english->getValue() => $english
                ],
                $english,
                [],
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                        'offset' => 0
                    ]
                ]
            )
        ];
    }


    /**
     * @test
     */
    public function detectValueDetectsValueSerializedInComponentContextsFirstUriPathSegmentPart()
    {
        $detector = new ContentDimensionDetection\UriPathSegmentContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/en-GB'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $detectorOverrideOptions = $this->contentDimensions['language']->getConfigurationValue('resolution');
        $detectorOverrideOptions['delimiter'] = '-';

        $this->assertSame(
            $this->contentDimensions['language']->getValue('en'),
            $detector->detectValue(
                $this->contentDimensions['language'],
                $componentContext,
                $detectorOverrideOptions
            )
        );
    }

    /**
     * @test
     */
    public function detectValueDetectsValueSerializedInComponentContextsSecondUriPathSegmentPart()
    {
        $detector = new ContentDimensionDetection\UriPathSegmentContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/en-GB'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $detectorOverrideOptions = $this->contentDimensions['market']->getConfigurationValue('resolution');
        $detectorOverrideOptions['delimiter'] = '-';

        $this->assertSame(
            $this->contentDimensions['market']->getValue('GB'),
            $detector->detectValue(
                $this->contentDimensions['market'],
                $componentContext,
                $detectorOverrideOptions
            )
        );
    }

    /**
     * @test
     */
    public function detectValueDetectsValueSerializedInComponentContextsBackendUrisFirstUriPathSegmentPart()
    {
        $detector = new ContentDimensionDetection\UriPathSegmentContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/en-GB@user-me'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $detectorOverrideOptions = $this->contentDimensions['language']->getConfigurationValue('resolution');
        $detectorOverrideOptions['delimiter'] = '-';

        $this->assertSame(
            $this->contentDimensions['language']->getValue('en'),
            $detector->detectValue(
                $this->contentDimensions['language'],
                $componentContext,
                $detectorOverrideOptions
            )
        );
    }

    /**
     * @test
     */
    public function detectValueDetectsValueSerializedInComponentContextsBackendUrisSecondUriPathSegmentPart()
    {
        $detector = new ContentDimensionDetection\UriPathSegmentContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/en-GB@user-me'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $detectorOverrideOptions = $this->contentDimensions['market']->getConfigurationValue('resolution');
        $detectorOverrideOptions['delimiter'] = '-';

        $this->assertSame(
            $this->contentDimensions['market']->getValue('GB'),
            $detector->detectValue(
                $this->contentDimensions['market'],
                $componentContext,
                $detectorOverrideOptions
            )
        );
    }

    /**
     * @test
     */
    public function detectValueDoesNotDetectValueFromComponentContextWithoutMatchingSerialization()
    {
        $detector = new ContentDimensionDetection\UriPathSegmentContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/wat'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $detectorOverrideOptions = $this->contentDimensions['market']->getConfigurationValue('resolution');
        $detectorOverrideOptions['delimiter'] = '-';

        $this->assertSame(
            null,
            $detector->detectValue(
                $this->contentDimensions['market'],
                $componentContext,
                $detectorOverrideOptions
            )
        );
    }

    /**
     * @test
     */
    public function detectValueDoesNotDetectValueFromComponentContextWithoutUriPathSegment()
    {
        $detector = new ContentDimensionDetection\UriPathSegmentContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $detectorOverrideOptions = $this->contentDimensions['market']->getConfigurationValue('resolution');
        $detectorOverrideOptions['delimiter'] = '-';

        $this->assertSame(
            null,
            $detector->detectValue(
                $this->contentDimensions['market'],
                $componentContext,
                $detectorOverrideOptions
            )
        );
    }
}
