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
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\BasicContentDimensionResolutionMode;
use Neos\Neos\Http\ContentDimensionLinking\NullContentDimensionValueUriProcessor;

/**
 * Test cases for the NullContentDimensionValueUriProcessor
 */
class NullContentDimensionValueUriProcessorTest extends UnitTestCase
{
    /**
     * @var Dimension\ContentDimension
     */
    protected $lunarPhase;

    public function setUp()
    {
        parent::setUp();
        $defaultLunarPhase = new Dimension\ContentDimensionValue('fullMoon', null, [], ['resolution' => ['value' => 'fullMoon']]);

        $this->lunarPhase = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('market'),
            [
                (string)$defaultLunarPhase => $defaultLunarPhase
            ],
            $defaultLunarPhase,
            [],
            [
                'resolution' => [
                    'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_NULL,
                ]
            ]
        );
    }


    /**
     * @test
     */
    public function processUriConstraintsDoesNotModifyUriConstraints()
    {
        $uriProcessor = new NullContentDimensionValueUriProcessor();
        $uriConstraints = UriConstraints::create();

        $processedUriConstraints = $uriProcessor->processUriConstraints(
            $uriConstraints,
            $this->lunarPhase,
            $this->lunarPhase->getValue('fullMoon'),
            []
        );

        $this->assertSame(
            $uriConstraints,
            $processedUriConstraints
        );
    }
}
