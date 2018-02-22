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
 * Test cases for the HostSuffixContentDimensionValueDetector
 */
class HostSuffixContentDimensionValueDetectorTest extends UnitTestCase
{
    /**
     * @var array|Dimension\ContentDimension[]
     */
    protected $contentDimensions;


    public function setUp()
    {
        parent::setUp();

        $greatBritain = new Dimension\ContentDimensionValue('GB', new Dimension\ContentDimensionValueSpecializationDepth(0), [], ['resolution' => ['value' => 'co.uk']]);
        $germany = new Dimension\ContentDimensionValue('DE', new Dimension\ContentDimensionValueSpecializationDepth(0), [], ['resolution' => ['value' => 'de']]);
        $this->contentDimensions = [
            'market' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('market'),
                [
                    $greatBritain->getValue() => $greatBritain,
                    $germany->getValue() => $germany
                ],
                $greatBritain,
                [],
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTSUFFIX
                    ]
                ]
            )
        ];
    }


    /**
     * @test
     */
    public function detectPresetDetectsPresetFromComponentContextWithMatchingTopLevelDomain()
    {
        $detector = new ContentDimensionDetection\HostSuffixContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.de'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame(
            $this->contentDimensions['market']->getValue('DE'),
            $detector->detectValue(
                $this->contentDimensions['market'],
                $componentContext
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDetectsPresetFromComponentContextWithMatchingCompositeDomain()
    {
        $detector = new ContentDimensionDetection\HostSuffixContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.co.uk'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame(
            $this->contentDimensions['market']->getValue('GB'),
            $detector->detectValue(
                $this->contentDimensions['market'],
                $componentContext
            )
        );
    }

    /**
     * @test
     */
    public function detectPresetDetectsNoPresetFromComponentContextWithNotMatchingTopLevelDomain()
    {
        $detector = new ContentDimensionDetection\HostSuffixContentDimensionValueDetector();
        $httpRequest = Http\Request::create(new Http\Uri('https://domain.fr'));
        $httpResponse = new Http\Response();
        $componentContext = new Http\Component\ComponentContext($httpRequest, $httpResponse);

        $this->assertSame(
            null,
            $detector->detectValue(
                $this->contentDimensions['market'],
                $componentContext
            )
        );
    }
}
