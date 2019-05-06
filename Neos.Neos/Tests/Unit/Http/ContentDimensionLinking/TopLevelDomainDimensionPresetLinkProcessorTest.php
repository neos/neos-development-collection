<?php
namespace Neos\Neos\Tests\Unit\Http\ContentDimensionLinking;

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
 * Test cases for the TopLevelDomainDimensionPresetLinkProcessor
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
        'defaultPreset' => 'GB',
        'allowEmptyValue' => false,
        'presets' => [
            'GB' => [
                'values' => ['GB'],
                'resolutionValue' => 'co.uk'
            ],
            'FR' => [
                'values' => ['FR'],
                'resolutionValue' => 'fr'
            ]
        ]
    ];

    public function hostSuffixProvider(): array
    {
        return [
            ['GB', '.co.uk', ['.co.uk', '.fr']],
            ['FR', '.fr', ['.co.uk', '.fr']]
        ];
    }

    /**
     * @test
     * @dataProvider hostSuffixProvider
     * @param string $presetKey
     * @param string $expectedHostSuffix
     * @param array $expectedReplaceSuffixes
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function processUriConstraintsAddsHostSuffixWithReplacementsIfGiven(string $presetKey, string $expectedHostSuffix, array $expectedReplaceSuffixes)
    {
        $linkProcessor = new TopLevelDomainDimensionPresetLinkProcessor();
        $uriConstraints = UriConstraints::create();

        $processedUriConstraints = $linkProcessor->processUriConstraints(
            $uriConstraints,
            'market',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['presets'][$presetKey],
            []
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            [
                'suffix' => $expectedHostSuffix,
                'replaceSuffixes' => $expectedReplaceSuffixes
            ],
            $constraints['hostSuffix']
        );
    }
}
