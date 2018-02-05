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
 * Test cases for the BackendUriContentDimensionValueDetector
 */
class BackendUriContentDimensionValueDetectorTest extends UnitTestCase
{
    /**
     * @var array|Dimension\ContentDimension[]
     */
    protected $contentDimensions;


    public function setUp()
    {
        parent::setUp();

        $english = new Dimension\ContentDimensionValue('en', new Dimension\ContentDimensionValueSpecializationDepth(0), [], ['resolution' => ['value' => 'en']]);
        $german = new Dimension\ContentDimensionValue('de', new Dimension\ContentDimensionValueSpecializationDepth(0), [], ['resolution' => ['value' => 'de']]);
        $dutch = new Dimension\ContentDimensionValue('nl', new Dimension\ContentDimensionValueSpecializationDepth(1), [], ['resolution' => ['value' => 'nl']]);
        $this->contentDimensions = [
            'language' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('language'),
                [
                    $english->getValue() => $english,
                    $german->getValue() => $german,
                    $dutch->getValue() => $dutch
                ],
                $english,
                [
                    new Dimension\ContentDimensionValueVariationEdge($dutch, $german)
                ],
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT
                    ]
                ]
            )
        ];
    }


    /**
     * @test
     */
    public function detectValueDetectsValueFromComponentContextWithBackendUrlContainingSerializedValue()
    {
        $detector = new ContentDimensionDetection\BackendUriContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/@user-me;language=nl'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame(
            $this->contentDimensions['language']->getValue('nl'),
            $detector->detectValue(
                $this->contentDimensions['language'],
                $componentContext
            )
        );
    }

    /**
     * @test
     */
    public function detectValueDetectsNoValueFromComponentContextWithBackendUrlNotContainingSerializedValue()
    {
        $detector = new ContentDimensionDetection\BackendUriContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/@user-me'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame(
            null,
            $detector->detectValue(
                $this->contentDimensions['language'],
                $componentContext
            )
        );
    }

    /**
     * @test
     */
    public function detectValueDetectsValueFromComponentContextWithBackendUrlContainingSerializedValueDifferentFromTheFrontendUrlValue()
    {
        $detector = new ContentDimensionDetection\BackendUriContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com/fr_EU/@user-me;language=nl'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame(
            $this->contentDimensions['language']->getValue('nl'),
            $detector->detectValue(
                $this->contentDimensions['language'],
                $componentContext
            )
        );
    }
}
