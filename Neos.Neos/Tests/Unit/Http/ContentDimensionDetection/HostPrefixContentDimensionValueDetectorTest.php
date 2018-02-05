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
 * Test cases for the HostPrefixContentDimensionValueDetector
 */
class HostPrefixContentDimensionValueDetectorTest extends UnitTestCase
{
    /**
     * @var array|Dimension\ContentDimension[]
     */
    protected $contentDimensions;


    public function setUp()
    {
        parent::setUp();

        $english = new Dimension\ContentDimensionValue('en', new Dimension\ContentDimensionValueSpecializationDepth(0), [], ['resolution' => ['value' => '']]);
        $german = new Dimension\ContentDimensionValue('de', new Dimension\ContentDimensionValueSpecializationDepth(0), [], ['resolution' => ['value' => 'de']]);
        $this->contentDimensions = [
            'language' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('language'),
                [
                    $english->getValue() => $english,
                    $german->getValue() => $german
                ],
                $english,
                [],
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTPREFIX
                    ]
                ]
            )
        ];
    }


    /**
     * @test
     */
    public function detectValueDetectsValueFromComponentContextWithMatchingHostPrefix()
    {
        $detector = new ContentDimensionDetection\HostPrefixContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://de.domain.com'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame(
            $this->contentDimensions['language']->getValue('de'),
            $detector->detectValue(
                $this->contentDimensions['language'],
                $componentContext
            )
        );
    }

    /**
     * @test
     */
    public function detectValueDetectsNoValueFromComponentContextWithoutHostPrefix()
    {
        $detector = new ContentDimensionDetection\HostPrefixContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.com'));
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
    public function detectValueDetectsNoValueFromComponentContextWithNotMatchingHostPrefix()
    {
        $detector = new ContentDimensionDetection\HostPrefixContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://www.domain.com'));
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
}
