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
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Domain\Context\Content\ContentQuery;
use Neos\Neos\Http\BasicContentDimensionResolutionMode;
use Neos\Neos\Http\ContentSubgraphUriProcessor;
use Neos\Utility\ObjectAccess;

/**
 * Test cases for the ContentSubgraphUriProcessor
 */
class ContentSubgraphUriProcessorTest extends FunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $world = new Dimension\ContentDimensionValue('WORLD', null, [], ['resolution' => ['value' => '.com']]);
        $greatBritain = new Dimension\ContentDimensionValue('GB', new Dimension\ContentDimensionValueSpecializationDepth(1), [], ['resolution' => ['value' => '.co.uk']]);
        $germany = new Dimension\ContentDimensionValue('DE', new Dimension\ContentDimensionValueSpecializationDepth(1), [], ['resolution' => ['value' => '.de']]);

        $defaultSeller = new Dimension\ContentDimensionValue('default', null, [], ['resolution' => ['value' => 'default']]);
        $sellerA = new Dimension\ContentDimensionValue('sellerA', new Dimension\ContentDimensionValueSpecializationDepth(1), [], ['resolution' => ['value' => 'sellerA']]);

        $defaultChannel = new Dimension\ContentDimensionValue('default', null, [], ['resolution' => ['value' => 'default']]);
        $channelA = new Dimension\ContentDimensionValue('channelA', new Dimension\ContentDimensionValueSpecializationDepth(1), [], ['resolution' => ['value' => 'channelA']]);

        $english = new Dimension\ContentDimensionValue('en', null, [], ['resolution' => ['value' => '']]);
        $german = new Dimension\ContentDimensionValue('de', null, [], ['resolution' => ['value' => 'de.']]);

        $contentDimensions = [
            'market' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('market'),
                [
                    $world->getValue() => $world,
                    $greatBritain->getValue() => $greatBritain,
                    $germany->getValue() => $germany
                ],
                $world,
                [
                    new Dimension\ContentDimensionValueVariationEdge($greatBritain, $world),
                    new Dimension\ContentDimensionValueVariationEdge($germany, $world)
                ],
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTSUFFIX
                    ]
                ]
            ),
            'seller' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('seller'),
                [
                    $defaultSeller->getValue() => $defaultSeller,
                    $sellerA->getValue() => $sellerA
                ],
                $defaultSeller,
                [
                    new Dimension\ContentDimensionValueVariationEdge($sellerA, $defaultSeller)
                ],
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                        'options' => [
                            'allowEmptyValue' => true
                        ]
                    ]
                ]
            ),
            'channel' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('channel'),
                [
                    $defaultChannel->getValue() => $defaultChannel,
                    $channelA->getValue() => $channelA
                ],
                $defaultChannel,
                [
                    new Dimension\ContentDimensionValueVariationEdge($channelA, $defaultChannel)
                ],
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                        'options' => [
                            'allowEmptyValue' => true
                        ]
                    ]
                ]
            ),
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
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTPREFIX,
                        'options' => [
                            'allowEmptyValue' => true
                        ]
                    ]
                ]
            )
        ];

        $dimensionPresetSource = $this->objectManager->get(Dimension\ContentDimensionSourceInterface::class);
        $this->inject($dimensionPresetSource, 'contentDimensions', $contentDimensions);
    }

    /**
     * @test
     * @throws \Neos\Neos\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function resolveDimensionUriConstraintsExtractsUriConstraintsFromSubgraph()
    {
        $uriProcessor = new ContentSubgraphUriProcessor();

        $contentQuery = new ContentQuery(
            new NodeAggregateIdentifier(),
            WorkspaceName::forLive(),
            new DimensionSpacePoint([
                'market' => 'GB',
                'seller' => 'sellerA',
                'channel' => 'channelA',
                'language' => 'en'
            ]),
            new NodeAggregateIdentifier(),
            new NodeIdentifier()
        );
        $dimensionUriConstraints = $uriProcessor->resolveDimensionUriConstraints($contentQuery, false);
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
            'sellerA_channelA/',
            $constraints['pathPrefix']
        );
    }
}
