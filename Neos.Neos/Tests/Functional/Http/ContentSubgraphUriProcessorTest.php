<?php

namespace Neos\Neos\Tests\Functional\Http;

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
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Http\ContentDimensionResolutionMode;

/**
 * Test case for the ContentSubgraphUriProcessor
 */
class ContentSubgraphUriProcessorTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $dimensionPresets = [
        'market' => [
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN,
            ],
            'defaultPreset' => 'EU',
            'default' => 'EU',
            'presets' => [
                'WORLD' => [
                    'values' => ['WORLD'],
                    'resolutionValue' => 'com'
                ],
                'GB' => [
                    'values' => ['GB', 'WORLD'],
                    'resolutionValue' => 'co.uk'
                ],
                'DE' => [
                    'values' => ['DE', 'WORLD'],
                    'resolutionValue' => 'de'
                ]
            ]
        ],
        'seller' => [
            'defaultPreset' => 'default',
            'default' => 'default',
            'presets' => [
                'default' => [
                    'values' => ['default'],
                    'resolutionValue' => 'default'
                ],
                'sellerA' => [
                    'values' => ['sellerA', 'default'],
                    'resolutionValue' => 'sellerA'
                ]
            ]
        ],
        'channel' => [
            'defaultPreset' => 'default',
            'default' => 'default',
            'presets' => [
                'default' => [
                    'values' => ['default'],
                    'resolutionValue' => 'default'
                ],
                'channelA' => [
                    'values' => ['channelA', 'default'],
                    'resolutionValue' => 'channelA'
                ]
            ]
        ],
        'language' => [
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN,
            ],
            'default' => 'en',
            'defaultPreset' => 'en',
            'presets' => [
                'en' => [
                    'values' => ['en'],
                    'resolutionValue' => ''
                ],
                'de' => [
                    'values' => ['de'],
                    'resolutionValue' => 'de'
                ]
            ]
        ]
    ];
}
