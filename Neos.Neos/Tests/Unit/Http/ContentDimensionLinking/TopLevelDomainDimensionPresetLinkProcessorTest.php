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
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\ContentDimensionLinking\TopLevelDomainDimensionPresetLinkProcessor;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Utility\ObjectAccess;

/**
 * Test case for the SubdomainDimensionPresetLinkProcessor
 */
class TopLevelDomainDimensionPresetLinkProcessorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'resolution' => [
            'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN,
        ],
        'defaultPreset' => 'en',
        'allowEmptyValue' => true,
        'presets' => [
            'en' => [
                'values' => ['en'],
                'resolutionValue' => 'com'
            ],
            'fr' => [
                'values' => ['fr'],
                'resolutionValue' => 'fr'
            ]
        ]
    ];


    /**
     * @test
     */
    public function processUriConstraintsAddsHostSuffixWithReplacementsIfGiven()
    {
        $linkProcessor = new TopLevelDomainDimensionPresetLinkProcessor();
        $uriConstraints = UriConstraints::create();

        $processedUriConstraints = $linkProcessor->processUriConstraints(
            $uriConstraints,
            'market',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['presets']['fr'],
            []
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            [
                'suffix' => '.fr',
                'replaceSuffixes' => ['.com', '.fr']
            ],
            $constraints['hostSuffix']
        );
    }
}
