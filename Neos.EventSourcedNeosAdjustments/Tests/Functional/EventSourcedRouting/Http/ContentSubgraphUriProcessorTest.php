<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Tests\Functional\EventSourcedRouting\Http;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimension;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionConstraints;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionConstraintSet;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValues;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValueSpecializationDepth;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValueVariationEdge;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValueVariationEdges;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\BasicContentDimensionResolutionMode;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentSubgraphUriProcessor;
use Neos\Utility\Exception\PropertyNotAccessibleException;
use Neos\Utility\ObjectAccess;

class ContentSubgraphUriProcessorTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $emptyConstraints = new ContentDimensionConstraintSet([]);
        $world = new ContentDimensionValue('WORLD', null, $emptyConstraints, ['resolution' => ['value' => '.com']]);
        $greatBritain = new ContentDimensionValue('GB', new ContentDimensionValueSpecializationDepth(1), $emptyConstraints, ['resolution' => ['value' => '.co.uk']]);
        $germany = new ContentDimensionValue('DE', new ContentDimensionValueSpecializationDepth(1), $emptyConstraints, ['resolution' => ['value' => '.de']]);

        $defaultSeller = new ContentDimensionValue('default', null, $emptyConstraints, ['resolution' => ['value' => 'default']]);
        $sellerA = new ContentDimensionValue('sellerA', new ContentDimensionValueSpecializationDepth(1), $emptyConstraints, ['resolution' => ['value' => 'sellerA']]);

        $defaultChannel = new ContentDimensionValue('default', null, $emptyConstraints, ['resolution' => ['value' => 'default']]);
        $channelA = new ContentDimensionValue('channelA', new ContentDimensionValueSpecializationDepth(1), $emptyConstraints, ['resolution' => ['value' => 'channelA']]);

        $english = new ContentDimensionValue('en', null, $emptyConstraints, ['resolution' => ['value' => '']]);
        $german = new ContentDimensionValue('de', null, $emptyConstraints, ['resolution' => ['value' => 'de.']]);

        $contentDimensions = [
            'market' => new ContentDimension(
                new ContentDimensionIdentifier('market'),
                new ContentDimensionValues([$world, $greatBritain, $germany]),
                $world,
                new ContentDimensionValueVariationEdges([
                    new ContentDimensionValueVariationEdge($greatBritain, $world),
                    new ContentDimensionValueVariationEdge($germany, $world)
                ]),
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTSUFFIX
                    ]
                ]
            ),
            'seller' => new ContentDimension(
                new ContentDimensionIdentifier('seller'),
                new ContentDimensionValues([$defaultSeller, $sellerA]),
                $defaultSeller,
                new ContentDimensionValueVariationEdges([
                    new ContentDimensionValueVariationEdge($sellerA, $defaultSeller)
                ]),
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                        'options' => [
                            'allowEmptyValue' => true
                        ]
                    ]
                ]
            ),
            'channel' => new ContentDimension(
                new ContentDimensionIdentifier('channel'),
                new ContentDimensionValues([$defaultChannel, $channelA]),
                $defaultChannel,
                new ContentDimensionValueVariationEdges([
                    new ContentDimensionValueVariationEdge($channelA, $defaultChannel)
                ]),
                [
                    'resolution' => [
                        'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                        'options' => [
                            'allowEmptyValue' => true
                        ]
                    ]
                ]
            ),
            'language' => new ContentDimension(
                new ContentDimensionIdentifier('language'),
                new ContentDimensionValues([$english, $german]),
                $english,
                new ContentDimensionValueVariationEdges([]),
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

        $dimensionPresetSource = $this->objectManager->get(ContentDimensionSourceInterface::class);
        $this->inject($dimensionPresetSource, 'contentDimensions', $contentDimensions);
    }

    /**
     * @test
     * @throws PropertyNotAccessibleException
     * @throws InvalidContentDimensionValueUriProcessorException
     * @throws \Exception
     */
    public function resolveDimensionUriConstraintsExtractsUriConstraintsFromSubgraph()
    {
        $uriProcessor = new ContentSubgraphUriProcessor();

        $contentQuery = new NodeAddress(
            ContentStreamIdentifier::create(),
            DimensionSpacePoint::fromArray([
                'market' => 'GB',
                'seller' => 'sellerA',
                'channel' => 'channelA',
                'language' => 'en'
            ]),
            NodeAggregateIdentifier::create(),
            WorkspaceName::forLive()
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
