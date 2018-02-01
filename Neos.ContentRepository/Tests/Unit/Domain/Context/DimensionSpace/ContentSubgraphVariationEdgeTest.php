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

/**
 * Unit test cases for content subgraph variation edges
 */
class ContentSubgraphVariationEdgeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function initializationCorrectlyDeterminesVariationWeight()
    {
        $specialization = new DimensionSpace\ContentSubgraph([
            'dimensionA' => new Dimension\ContentDimensionValue('value0', new Dimension\ContentDimensionValueSpecializationDepth(0)),
            'dimensionB' => new Dimension\ContentDimensionValue('value1', new Dimension\ContentDimensionValueSpecializationDepth(1)),
            'dimensionC' => new Dimension\ContentDimensionValue('value2', new Dimension\ContentDimensionValueSpecializationDepth(3))
        ]);
        $generalization = new DimensionSpace\ContentSubgraph([
            'dimensionA' => new Dimension\ContentDimensionValue('value0', new Dimension\ContentDimensionValueSpecializationDepth(0)),
            'dimensionB' => new Dimension\ContentDimensionValue('value1', new Dimension\ContentDimensionValueSpecializationDepth(0)),
            'dimensionC' => new Dimension\ContentDimensionValue('value2', new Dimension\ContentDimensionValueSpecializationDepth(1))
        ]);
        $variationEdge = new DimensionSpace\ContentSubgraphVariationEdge($specialization, $generalization);

        $this->assertEquals(new DimensionSpace\ContentSubgraphVariationWeight([
            'dimensionA' => new Dimension\ContentDimensionValueSpecializationDepth(0),
            'dimensionB' => new Dimension\ContentDimensionValueSpecializationDepth(1),
            'dimensionC' => new Dimension\ContentDimensionValueSpecializationDepth(2)
        ]), $variationEdge->getWeight());
    }
}
