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
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

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

    public function nodeDataProvider(): array
    {
        return [
            [
                '/sites/wat',
                ['GB', 'WORLD'], ['sellerA', 'default'], ['channelA', 'default'], ['en'],
                '', ['de.'], '.co.uk', ['.com', '.co.uk', '.de'], 'sellerA_channelA'
            ],
            [
                '/sites/wat/home',
                ['GB', 'WORLD'], ['sellerA', 'default'], ['channelA', 'default'], ['en'],
                '', ['de.'], '.co.uk', ['.com', '.co.uk', '.de'], 'sellerA_channelA/'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider nodeDataProvider
     * @param string $nodePath
     * @param array $marketValues
     * @param array $sellerValues
     * @param array $channelValues
     * @param array $languageValues
     * @param string $expectedHostPrefix
     * @param array $expectedReplaceHostPrefixes
     * @param string $expectedHostSuffix
     * @param array $expectedReplaceHostSuffixes
     * @param string $expectedPathPrefix
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function resolveDimensionUriConstraintsExtractsUriConstraintsFromSubgraph(
        string $nodePath,
        array $marketValues,
        array $sellerValues,
        array $channelValues,
        array $languageValues,
        string $expectedHostPrefix,
        array $expectedReplaceHostPrefixes,
        string $expectedHostSuffix,
        array $expectedReplaceHostSuffixes,
        string $expectedPathPrefix
    ) {
        $uriProcessor = new ContentSubgraphUriProcessor();

        $dimensionPresetSource = $this->objectManager->get(ContentDimensionPresetSourceInterface::class);
        $dimensionPresetSource->setConfiguration($this->dimensionPresets);

        $workspaceName = 'live';
        $workspace = new Workspace($workspaceName);
        $node = new Node(
            new NodeData($nodePath, $workspace),
            new ContentContext(
                $workspaceName,
                new \DateTime(),
                [
                    'market' => $marketValues,
                    'seller' => $sellerValues,
                    'channel' => $channelValues,
                    'language' => $languageValues
                ],
                [
                    'market' => reset($marketValues),
                    'seller' => reset($sellerValues),
                    'channel' => reset($channelValues),
                    'language' => reset($languageValues)
                ], false, false, false
            )
        );

        $dimensionUriConstraints = $uriProcessor->resolveDimensionUriConstraints($node);
        $constraints = ObjectAccess::getProperty($dimensionUriConstraints, 'constraints', true);

        $this->assertSame(
            [
                'prefix' => $expectedHostPrefix,
                'replacePrefixes' => $expectedReplaceHostPrefixes
            ],
            $constraints['hostPrefix']
        );
        $this->assertSame(
            [
                'suffix' => $expectedHostSuffix,
                'replaceSuffixes' => $expectedReplaceHostSuffixes
            ],
            $constraints['hostSuffix']
        );
        $this->assertSame(
            $expectedPathPrefix,
            $constraints['pathPrefix']
        );
    }
}
