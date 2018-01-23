<?php

namespace Neos\ContentRepository\Tests\Unit\Domain\Context\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\ContentRepository\Domain\Context\DimensionSpace;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;

/**
 * Test cases for the inter dimensional variation graph
 */
class InterDimensionalVariationGraphTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createContentSubgraphRegistersSubgraph()
    {
        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $graph = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);
        /** @var DimensionSpace\ContentSubgraph $contentSubgraph */
        $contentSubgraph = $graph->_call('createContentSubgraph', ['test' => new Dimension\ContentDimensionValue('a')]);

        $this->assertSame($contentSubgraph, $graph->getSubgraphByDimensionSpacePoint($contentSubgraph->getDimensionSpacePoint()));
    }

    /**
     * @test
     */
    public function connectSubgraphsAddsGeneralizationToSpecialization()
    {
        $generalizationValue = new Dimension\ContentDimensionValue('generalization', new Dimension\ContentDimensionValueSpecializationDepth(0));
        $specializationValue = new Dimension\ContentDimensionValue('specialization', new Dimension\ContentDimensionValueSpecializationDepth(1));

        $dimensions = ['test' =>
            new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('test'),
                [
                    (string) $generalizationValue => $generalizationValue,
                    (string) $specializationValue => $specializationValue
                ],
                $generalizationValue,
                [
                    new Dimension\ContentDimensionValueVariationEdge($specializationValue, $generalizationValue)
                ]
            )
        ];

        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $graph = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);
        $this->inject($graph, 'contentDimensionSource', $this->createDimensionSourceMock($dimensions));

        $generalization = new DimensionSpace\ContentSubgraph(['test' => $generalizationValue]);
        $specialization = new DimensionSpace\ContentSubgraph(['test' => $specializationValue]);

        $graph->_call('connectSubgraphs', $specialization, $generalization);

        $this->assertContains($generalization, $specialization->getGeneralizations());
    }

    /**
     * @test
     */
    public function connectSubgraphsAddsSpecializationToGeneralization()
    {
        $generalizationValue = new Dimension\ContentDimensionValue('generalization', new Dimension\ContentDimensionValueSpecializationDepth(0));
        $specializationValue = new Dimension\ContentDimensionValue('specialization', new Dimension\ContentDimensionValueSpecializationDepth(1));

        $dimensions = ['test' =>
            new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('test'),
                [
                    (string) $generalizationValue => $generalizationValue,
                    (string) $specializationValue => $specializationValue
                ],
                $generalizationValue,
                [
                    new Dimension\ContentDimensionValueVariationEdge($specializationValue, $generalizationValue)
                ]
            )
        ];

        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $graph = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);
        $this->inject($graph, 'contentDimensionSource', $this->createDimensionSourceMock($dimensions));

        $generalization = new DimensionSpace\ContentSubgraph(['test' => $generalizationValue]);
        $specialization = new DimensionSpace\ContentSubgraph(['test' => $specializationValue]);

        $graph->_call('connectSubgraphs', $specialization, $generalization);

        $this->assertContains($specialization, $generalization->getSpecializations());
    }

    /**
     * @test
     * @dataProvider dimensionValueCombinationProvider
     * @param array $specializationDimensionCombination
     * @param array $generalizationDimensionCombination
     * @param array $expectedWeight
     */
    public function calculateSpecializationWeightAggregatesCorrectWeightPerDimension(array $specializationDimensionCombination, array $generalizationDimensionCombination, array $expectedWeight)
    {
        $primary0 = new Dimension\ContentDimensionValue('primary0', new Dimension\ContentDimensionValueSpecializationDepth(0));
        $primary1 = new Dimension\ContentDimensionValue('primary1', new Dimension\ContentDimensionValueSpecializationDepth(1));
        $primary2 = new Dimension\ContentDimensionValue('primary2', new Dimension\ContentDimensionValueSpecializationDepth(2));

        $secondary0a = new Dimension\ContentDimensionValue('secondary0a', new Dimension\ContentDimensionValueSpecializationDepth(0));
        $secondary0b = new Dimension\ContentDimensionValue('secondary0b', new Dimension\ContentDimensionValueSpecializationDepth(0));

        $tertiary0 = new Dimension\ContentDimensionValue('tertiary0', new Dimension\ContentDimensionValueSpecializationDepth(0));
        $tertiary1a = new Dimension\ContentDimensionValue('tertiary1a', new Dimension\ContentDimensionValueSpecializationDepth(1));
        $tertiary1b = new Dimension\ContentDimensionValue('tertiary1b', new Dimension\ContentDimensionValueSpecializationDepth(1));

        /** @var array|Dimension\ContentDimension[] $dimensions */
        $dimensions = [
            'primary' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('primary'),
                [
                    (string) $primary0 => $primary0,
                    (string) $primary1 => $primary1,
                    (string) $primary2 => $primary2
                ],
                $primary0,
                [
                    new Dimension\ContentDimensionValueVariationEdge($primary1, $primary0),
                    new Dimension\ContentDimensionValueVariationEdge($primary2, $primary1)
                ]
            ),
            'secondary' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('secondary'),
                [
                    (string) $secondary0a => $secondary0a,
                    (string) $secondary0b => $secondary0b
                ],
                $secondary0a
            ),
            'tertiary' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('tertiary'),
                [
                    (string) $tertiary0 => $tertiary0,
                    (string) $tertiary1a => $tertiary1a,
                    (string) $tertiary1b => $tertiary1b
                ],
                $tertiary0,
                [
                    new Dimension\ContentDimensionValueVariationEdge($tertiary1a, $tertiary0),
                    new Dimension\ContentDimensionValueVariationEdge($tertiary1b, $tertiary0)
                ]
            )
        ];

        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $graph = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);
        $this->inject($graph, 'contentDimensionSource', $this->createDimensionSourceMock($dimensions));

        array_walk($specializationDimensionCombination, function (&$value, $dimensionIdentifier) use ($dimensions) {
            $value = $dimensions[$dimensionIdentifier]->getValue($value);
        });
        $specializationContentSubgraph = new DimensionSpace\ContentSubgraph($specializationDimensionCombination);

        array_walk($generalizationDimensionCombination, function (&$value, $dimensionIdentifier) use ($dimensions) {
            $value = $dimensions[$dimensionIdentifier]->getValue($value);
        });
        $generalizationContentSubgraph = new DimensionSpace\ContentSubgraph($generalizationDimensionCombination);

        $this->assertEquals($expectedWeight, $graph->_call('calculateSpecializationWeight', $specializationContentSubgraph, $generalizationContentSubgraph));
    }

    public function dimensionValueCombinationProvider()
    {
        return [
            [
                ['primary' => 'primary2', 'secondary' => 'secondary0a', 'tertiary' => 'tertiary1a'],
                ['primary' => 'primary0', 'secondary' => 'secondary0a', 'tertiary' => 'tertiary0'],
                [
                    'primary' => new Dimension\ContentDimensionValueSpecializationDepth(2),
                    'secondary' => new Dimension\ContentDimensionValueSpecializationDepth(0),
                    'tertiary' => new Dimension\ContentDimensionValueSpecializationDepth(1)
                ]
            ],
            [
                ['primary' => 'primary1', 'secondary' => 'secondary0b', 'tertiary' => 'tertiary0'],
                ['primary' => 'primary0', 'secondary' => 'secondary0b', 'tertiary' => 'tertiary0'],
                [
                    'primary' => new Dimension\ContentDimensionValueSpecializationDepth(1),
                    'secondary' => new Dimension\ContentDimensionValueSpecializationDepth(0),
                    'tertiary' => new Dimension\ContentDimensionValueSpecializationDepth(0)
                ]
            ],
            [
                ['primary' => 'primary0', 'secondary' => 'secondary0b', 'tertiary' => 'tertiary1b'],
                ['primary' => 'primary0', 'secondary' => 'secondary0b', 'tertiary' => 'tertiary0'],
                [
                    'primary' => new Dimension\ContentDimensionValueSpecializationDepth(0),
                    'secondary' => new Dimension\ContentDimensionValueSpecializationDepth(0),
                    'tertiary' => new Dimension\ContentDimensionValueSpecializationDepth(1)
                ]
            ]
        ];
    }

    /**
     * @test
     */
    public function determineWeightNormalizationBaseEvaluatesToMaximumDimensionDepthPlusOne()
    {
        $firstDummy = new Dimension\ContentDimensionValue('firstDummy');
        $firstDimension = new Dimension\ContentDimension(new Dimension\ContentDimensionIdentifier('first'), [$firstDummy], $firstDummy);
        $firstDepth = new Dimension\ContentDimensionValueSpecializationDepth(random_int(0, 100));
        ObjectAccess::setProperty($firstDimension, 'maximumDepth', $firstDepth, true);

        $secondDummy = new Dimension\ContentDimensionValue('secondDummy');
        $secondDimension = new Dimension\ContentDimension(new Dimension\ContentDimensionIdentifier('second'), [$secondDummy], $secondDummy);
        $secondDepth = new Dimension\ContentDimensionValueSpecializationDepth(random_int(0, 100));
        ObjectAccess::setProperty($secondDimension, 'maximumDepth', $secondDepth, true);

        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $graph = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);
        $this->inject($graph, 'contentDimensionSource', $this->createDimensionSourceMock(['first' => $firstDimension, 'second' => $secondDimension]));

        $this->assertSame(max($firstDepth->getDepth(), $secondDepth->getDepth()) + 1, $graph->_call('determineWeightNormalizationBase'));
    }


    /**
     * @test
     * @dataProvider variationEdgeWeightNormalizationProvider
     * @param int $dimensionDepth
     * @param array $weight
     * @param int $expectedNormalizedWeight
     */
    public function normalizeWeightCorrectlyCalculatesNormalizedWeight(int $dimensionDepth, array $weight, int $expectedNormalizedWeight)
    {
        $primaryDummy = new Dimension\ContentDimensionValue('primaryDummy');
        $primaryDimension = new Dimension\ContentDimension(new Dimension\ContentDimensionIdentifier('primary'), [$primaryDummy], $primaryDummy);
        ObjectAccess::setProperty($primaryDimension, 'maximumDepth', new Dimension\ContentDimensionValueSpecializationDepth($dimensionDepth), true);
        $secondaryDummy = new Dimension\ContentDimensionValue('secondaryDummy');
        $secondaryDimension = new Dimension\ContentDimension(new Dimension\ContentDimensionIdentifier('secondary'), [$secondaryDummy], $secondaryDummy);
        ObjectAccess::setProperty($secondaryDimension, 'maximumDepth', new Dimension\ContentDimensionValueSpecializationDepth($dimensionDepth), true);
        $tertiaryDummy = new Dimension\ContentDimensionValue('tertiaryDummy');
        $tertiaryDimension = new Dimension\ContentDimension(new Dimension\ContentDimensionIdentifier('tertiary'), [$tertiaryDummy], $tertiaryDummy);
        ObjectAccess::setProperty($tertiaryDimension, 'maximumDepth', new Dimension\ContentDimensionValueSpecializationDepth($dimensionDepth), true);

        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $graph = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);
        $this->inject($graph, 'contentDimensionSource', $this->createDimensionSourceMock(['primary' => $primaryDimension, 'secondary' => $secondaryDimension, 'tertiary' => $tertiaryDimension]));

        $specialization = new DimensionSpace\ContentSubgraph([]);
        $generalization = new DimensionSpace\ContentSubgraph([]);
        $variationEdge = new DimensionSpace\VariationEdge($specialization, $generalization, $weight);

        $this->assertSame($expectedNormalizedWeight, $graph->_call('normalizeWeight', $variationEdge->getWeight()));
    }

    public function variationEdgeWeightNormalizationProvider()
    {
        return [
            [5, ['primary' => 5, 'secondary' => 4, 'tertiary' => 0], 204],
            [6, ['primary' => 0, 'secondary' => 3, 'tertiary' => 6], 27],
            [3, ['primary' => 1, 'secondary' => 3, 'tertiary' => 0], 28],
        ];
    }

    /**
     * @test
     * @dataProvider generalizationPrioritizationProvider
     * @param array $primaryGeneralizationWeight
     * @param array $secondaryGeneralizationWeight
     */
    public function getPrimaryGeneralizationReturnsGeneralizationWithLowestNormalizedWeight($primaryGeneralizationWeight, $secondaryGeneralizationWeight)
    {
        $primaryDummyValue1 = new Dimension\ContentDimensionValue('dummy1');
        $primaryDummyValue2 = new Dimension\ContentDimensionValue('dummy2');
        $primarySpecializationValue = new Dimension\ContentDimensionValue('specialization');

        $primaryDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('primary'),
            [
                (string) $primaryDummyValue1 => $primaryDummyValue1,
                (string) $primaryDummyValue2 => $primaryDummyValue2,
                (string) $primarySpecializationValue => $primarySpecializationValue
            ],
            $primaryDummyValue1
        );
        ObjectAccess::setProperty($primaryDimension, 'maximumDepth', new Dimension\ContentDimensionValueSpecializationDepth(5), true);

        $secondaryDummyValue = new Dimension\ContentDimensionValue('dummy');
        $secondaryDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('secondary'),
            [
                (string) $secondaryDummyValue => $secondaryDummyValue
            ],
            $secondaryDummyValue
        );
        ObjectAccess::setProperty($secondaryDimension, 'maximumDepth', new Dimension\ContentDimensionValueSpecializationDepth(5), true);

        $tertiaryDummyValue = new Dimension\ContentDimensionValue('dummy');
        $tertiaryDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('tertiary'),
            [
                (string) $tertiaryDummyValue => $tertiaryDummyValue
            ],
            $tertiaryDummyValue
        );
        ObjectAccess::setProperty($tertiaryDimension, 'maximumDepth', new Dimension\ContentDimensionValueSpecializationDepth(5), true);

        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $graph = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);
        $this->inject($graph, 'contentDimensionSource', $this->createDimensionSourceMock(['primary' => $primaryDimension, 'secondary' => $secondaryDimension, 'tertiary' => $tertiaryDimension]));

        $specialization = new DimensionSpace\ContentSubgraph([
            'primary' => $primarySpecializationValue,
            'secondary' => $secondaryDummyValue,
            'tertiary' => $tertiaryDummyValue
        ]);
        $primaryGeneralization = new DimensionSpace\ContentSubgraph([
            'primary' => $primaryDummyValue1,
            'secondary' => $secondaryDummyValue,
            'tertiary' => $tertiaryDummyValue
        ]);
        $secondaryGeneralization = new DimensionSpace\ContentSubgraph([
            'primary' => $primaryDummyValue2,
            'secondary' => $secondaryDummyValue,
            'tertiary' => $tertiaryDummyValue
        ]);
        new DimensionSpace\VariationEdge($specialization, $primaryGeneralization, $primaryGeneralizationWeight);
        new DimensionSpace\VariationEdge($specialization, $secondaryGeneralization, $secondaryGeneralizationWeight);

        $this->assertSame($primaryGeneralization, $graph->getPrimaryGeneralization($specialization));
    }

    public function generalizationPrioritizationProvider()
    {
        return [
            [
                ['primary' => 0, 'secondary' => 0, 'tertiary' => 1],
                ['primary' => 0, 'secondary' => 0, 'tertiary' => 2]
            ],
            [
                ['primary' => 0, 'secondary' => 0, 'tertiary' => 5],
                ['primary' => 0, 'secondary' => 1, 'tertiary' => 0]
            ],
            [
                ['primary' => 0, 'secondary' => 5, 'tertiary' => 5],
                ['primary' => 1, 'secondary' => 0, 'tertiary' => 0]
            ]
        ];
    }

    /**
     * @param array|Dimension\ContentDimension[] $contentDimensions
     * @return Dimension\ContentDimensionSourceInterface
     */
    protected function createDimensionSourceMock(array $contentDimensions): Dimension\ContentDimensionSourceInterface
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Dimension\ContentDimensionSourceInterface $mockDimensionSource */
        $mockDimensionSource = $this->createMock(Dimension\ContentDimensionSourceInterface::class);
        $mockDimensionSource->method('getContentDimensionsOrderedByPriority')
            ->willReturn($contentDimensions);

        return $mockDimensionSource;
    }
}
