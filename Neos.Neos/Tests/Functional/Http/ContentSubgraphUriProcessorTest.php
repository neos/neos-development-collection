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
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Neos\Http\ContentSubgraphUriProcessor;
use Neos\Utility\ObjectAccess;

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
            'defaultPreset' => 'WORLD',
            'default' => 'WORLD',
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

    /**
     * @test
     */
    public function resolveDimensionUriConstraintsExtractsUriConstraintsFromSubgraph()
    {
        $uriProcessor = new ContentSubgraphUriProcessor();

        $dimensionPresetSource = $this->objectManager->get(ContentDimensionPresetSourceInterface::class);
        $dimensionPresetSource->setConfiguration($this->dimensionPresets);

        $workspace = new Workspace('live');
        $node = new Node(
            new NodeData('/', $workspace),
            new ContentContext(
                'live',
                new \DateTime(),
                [
                    'market' => ['GB', 'WORLD'],
                    'seller' => ['sellerA', 'default'],
                    'channel' => ['channelA', 'default'],
                    'language' => ['en']
                ],
                [
                    'market' => 'GB',
                    'seller' => 'sellerA',
                    'channel' => 'channelA',
                    'language' => 'en'
                ], false, false , false
            )
        );

        $dimensionUriConstraints = $uriProcessor->resolveDimensionUriConstraints($node);
        $constraints = ObjectAccess::getProperty($dimensionUriConstraints, 'constraints', true);

        $this->assertSame(
            [
                'prefix' => '',
                'replacePrefixes' => ['de.']
            ],
            $constraints['hostPrefix']
        );
        $this->assertSame(
            [
                'suffix' => '.co.uk',
                'replaceSuffixes' => ['.com', '.co.uk', '.de']
            ],
            $constraints['hostSuffix']
        );
        $this->assertSame(
            'sellerA_channelA',
            $constraints['pathPrefix']
        );
    }
}
