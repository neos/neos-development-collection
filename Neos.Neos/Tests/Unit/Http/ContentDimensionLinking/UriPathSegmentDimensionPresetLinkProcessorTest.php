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
use Neos\Neos\Http\ContentDimensionLinking\UriPathSegmentDimensionPresetLinkProcessor;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Utility\ObjectAccess;

/**
 * Test cases for the UriPathSegmentDimensionPresetLinkProcessor
 */
class UriPathSegmentDimensionPresetLinkProcessorTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $dimensionConfiguration = [
        'market' => [
            'defaultPreset' => 'GB',
            'presets' => [
                'GB' => [
                    'values' => ['GB'],
                    'resolutionValue' => 'GB'
                ]
            ],
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                'options' => [
                    'offset' => 1
                ]
            ]
        ],
        'language' => [
            'defaultPreset' => 'en',
            'presets' => [
                'en' => [
                    'values' => ['en'],
                    'resolutionValue' => 'en'
                ]
            ],
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                'options' => [
                    'offset' => 0
                ]
            ]
        ],
    ];

    public function pathPrefixProvider(): array
    {
        return [
            ['-', 'GB', 'en', 'en-GB'],
            ['_', 'GB', 'en', 'en_GB'],
            ['_', 'GB', null, 'GB'],
            ['_', null, 'en', 'en']
        ];
    }

    /**
     * @test
     * @dataProvider pathPrefixProvider
     * @param string $delimiter
     * @param string $marketPresetKey
     * @param string $languagePresetKey
     * @param string $expectedPrefix
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function processUriConstraintsAddsFirstPathPrefix(string $delimiter, ?string $marketPresetKey, ?string $languagePresetKey, string $expectedPrefix)
    {
        $linkProcessor = new UriPathSegmentDimensionPresetLinkProcessor();
        $uriConstraints = UriConstraints::create();

        $offset = 0;
        if ($languagePresetKey) {
            $options = $this->dimensionConfiguration['language']['resolution']['options'];
            $options['delimiter'] = $delimiter;
            $options['offset'] = $offset;
            $uriConstraints = $linkProcessor->processUriConstraints(
                $uriConstraints,
                'language',
                $this->dimensionConfiguration['language'],
                $this->dimensionConfiguration['language']['presets'][$languagePresetKey],
                $options
            );
            $offset++;
        }

        if ($marketPresetKey) {
            $options = $this->dimensionConfiguration['market']['resolution']['options'];
            $options['delimiter'] = $delimiter;
            $options['offset'] = $offset;
            $uriConstraints = $linkProcessor->processUriConstraints(
                $uriConstraints,
                'market',
                $this->dimensionConfiguration['market'],
                $this->dimensionConfiguration['market']['presets'][$marketPresetKey],
                $options
            );
            $offset++;
        }

        $constraints = ObjectAccess::getProperty($uriConstraints, 'constraints', true);

        $this->assertSame(
            $expectedPrefix,
            $constraints['pathPrefix']
        );
    }
}
