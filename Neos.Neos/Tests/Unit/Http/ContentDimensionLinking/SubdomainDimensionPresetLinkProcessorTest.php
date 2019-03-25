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
use Neos\Neos\Http\ContentDimensionLinking\SubdomainDimensionPresetLinkProcessor;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Utility\ObjectAccess;

/**
 * Test case for the SubdomainDimensionPresetLinkProcessor
 */
class SubdomainDimensionPresetLinkProcessorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'resolution' => [
            'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN,
        ],
        'defaultPreset' => 'en',
        'allowEmptyValue' => true,
        'presets' => [
            'en' => [
                'values' => ['en'],
                'resolutionValue' => ''
            ],
            'de' => [
                'values' => ['de'],
                'resolutionValue' => 'de'
            ],
            'gsw' => [
                'values' => ['gsw', 'de'],
                'resolutionValue' => 'gsw.de'
            ],
            'fr' => [
                'values' => ['fr'],
                'resolutionValue' => 'fr'
            ]
        ]
    ];

    public function hostPrefixProvider(): array
    {
        return [
            ['fr', 'fr.', ['de.', 'gsw.de.', 'fr.']],
            ['en', '', ['de.', 'gsw.de.', 'fr.']],
            ['gsw', 'gsw.de.', ['de.', 'gsw.de.', 'fr.']]
        ];
    }

    /**
     * @test
     * @dataProvider hostPrefixProvider
     * @param string $presetKey
     * @param string $expectedHostPrefix
     * @param array $expectedReplacePrefixes
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function processUriConstraintsAddsHostPrefixWithReplacementsIfGiven(string $presetKey, string $expectedHostPrefix, array $expectedReplacePrefixes)
    {
        $linkProcessor = new SubdomainDimensionPresetLinkProcessor();
        $uriConstraints = UriConstraints::create();

        $processedUriConstraints = $linkProcessor->processUriConstraints(
            $uriConstraints,
            'language',
            $this->dimensionConfiguration,
            $this->dimensionConfiguration['presets'][$presetKey],
            []
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            [
                'prefix' => $expectedHostPrefix,
                'replacePrefixes' => $expectedReplacePrefixes
            ],
            $constraints['hostPrefix']
        );
    }
}
