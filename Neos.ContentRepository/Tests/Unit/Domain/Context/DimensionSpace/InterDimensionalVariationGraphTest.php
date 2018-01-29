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
 * Unit test cases for the inter dimensional variation graph
 */
class InterDimensionalVariationGraphTest extends UnitTestCase
{
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
            [
                5,
                [
                    'primary' => new Dimension\ContentDimensionValueSpecializationDepth(5),
                    'secondary' => new Dimension\ContentDimensionValueSpecializationDepth(4),
                    'tertiary' => new Dimension\ContentDimensionValueSpecializationDepth(0)
                ],
                204
            ],
            [
                6,
                [
                    'primary' => new Dimension\ContentDimensionValueSpecializationDepth(0),
                    'secondary' => new Dimension\ContentDimensionValueSpecializationDepth(3),
                    'tertiary' => new Dimension\ContentDimensionValueSpecializationDepth(6)
                ],
                27
            ],
            [
                3,
                [
                    'primary' => new Dimension\ContentDimensionValueSpecializationDepth(1),
                    'secondary' => new Dimension\ContentDimensionValueSpecializationDepth(3),
                    'tertiary' => new Dimension\ContentDimensionValueSpecializationDepth(0)
                ],
                28
            ],
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
