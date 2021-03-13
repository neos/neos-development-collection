<?php

namespace Neos\ContentRepository\DimensionSpace\Tests\Unit\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\ContentRepository\DimensionSpace\Dimension\Exception\ContentDimensionValueSpecializationDepthIsInvalid;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\ContentSubgraphVariationWeightsAreIncomparable;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Unit test cases for content subgraph variation weights
 */
class ContentSubgraphVariationWeightTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canBeComparedToReturnsTrueForEmptyComponents()
    {
        $weight = new DimensionSpace\ContentSubgraphVariationWeight([]);
        $weightToBeCompared = new DimensionSpace\ContentSubgraphVariationWeight([]);

        $this->assertTrue($weight->canBeComparedTo($weightToBeCompared));
    }

    /**
     * @test
     */
    public function canBeComparedToReturnsTrueForSameComponents()
    {
        $weight = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(0),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(1)
        ]);
        $weightToBeCompared = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(1),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(2)
        ]);

        $this->assertTrue($weight->canBeComparedTo($weightToBeCompared));
    }

    /**
     * @test
     */
    public function canBeComparedToReturnsFalseForDifferentComponents()
    {
        $weight = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(0),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(1)
        ]);
        $weightToBeCompared = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(1),
            'dimensionC' => new Dimension\ContentDimensionValueSpecializationDepth(3)
        ]);

        $this->assertFalse($weight->canBeComparedTo($weightToBeCompared));
    }

    /**
     * @test
     */
    public function decreaseByThrowsExceptionForIncomparableWeights()
    {
        $this->expectException(ContentSubgraphVariationWeightsAreIncomparable::class);
        $weight = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(0),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(1)
        ]);
        $weightToDecreaseBy = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(1),
            'dimensionC' => new Dimension\ContentDimensionValueSpecializationDepth(1)
        ]);

        $weight->decreaseBy($weightToDecreaseBy);
    }

    /**
     * @test
     */
    public function decreaseByThrowsExceptionForComponentsGreaterThanTheOriginal()
    {
        $this->expectException(ContentDimensionValueSpecializationDepthIsInvalid::class);
        $weight = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(0),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(1)
        ]);
        $weightToDecreaseBy = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(1),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(2)
        ]);

        $weight->decreaseBy($weightToDecreaseBy);
    }

    /**
     * @test
     * @throws ContentSubgraphVariationWeightsAreIncomparable
     */
    public function decreaseByCorrectlyDecreasesEachComponent()
    {
        $weight = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(3),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(2),
            'dimensionC' => new Dimension\ContentDimensionValueSpecializationDepth(3),
        ]);
        $weightToDecreaseBy = new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(0),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(1),
            'dimensionC' => new Dimension\ContentDimensionValueSpecializationDepth(3)
        ]);

        $this->assertEquals(new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(3),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(1),
            'dimensionC' => new Dimension\ContentDimensionValueSpecializationDepth(0)
        ]), $weight->decreaseBy($weightToDecreaseBy));
    }

    /**
     * @test
     * @dataProvider normalizationProvider
     * @param int $weightNormalizationBase
     * @param DimensionSpace\ContentSubgraphVariationWeight $weight
     * @param int $expectedNormalizedWeight
     */
    public function normalizeCorrectlyCalculatesNormalizedWeight(int $weightNormalizationBase, DimensionSpace\ContentSubgraphVariationWeight $weight, int $expectedNormalizedWeight)
    {
        $this->assertSame($expectedNormalizedWeight, $weight->normalize($weightNormalizationBase));
    }

    public function normalizationProvider()
    {
        return [
            [
                6,
                new DimensionSpace\ContentSubgraphVariationWeight([
                    'primary' => new Dimension\ContentDimensionValueSpecializationDepth(5),
                    'secondary' => new Dimension\ContentDimensionValueSpecializationDepth(4),
                    'tertiary' => new Dimension\ContentDimensionValueSpecializationDepth(0)
                ]),
                204
            ],
            [
                7,
                new DimensionSpace\ContentSubgraphVariationWeight([
                    'primary' => new Dimension\ContentDimensionValueSpecializationDepth(0),
                    'secondary' => new Dimension\ContentDimensionValueSpecializationDepth(3),
                    'tertiary' => new Dimension\ContentDimensionValueSpecializationDepth(6)
                ]),
                27
            ],
            [
                4,
                new DimensionSpace\ContentSubgraphVariationWeight([
                    'primary' => new Dimension\ContentDimensionValueSpecializationDepth(1),
                    'secondary' => new Dimension\ContentDimensionValueSpecializationDepth(3),
                    'tertiary' => new Dimension\ContentDimensionValueSpecializationDepth(0)
                ]),
                28
            ],
        ];
    }
}
