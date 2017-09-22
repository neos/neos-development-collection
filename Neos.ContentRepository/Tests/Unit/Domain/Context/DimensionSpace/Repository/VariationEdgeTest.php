<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Context\DimensionSpace\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\DimensionSpace;
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for variation edges
 */
class VariationEdgeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function variationEdgesAreRegisteredInFallbackAndVariantUponCreation()
    {
        $specialization = new DimensionSpace\Repository\ContentSubgraph(['test' => new Dimension\Model\ContentDimensionValue('a')]);
        $generalization = new DimensionSpace\Repository\ContentSubgraph(['test' => new Dimension\Model\ContentDimensionValue('b')]);

        $variationEdge = new DimensionSpace\Repository\VariationEdge($specialization, $generalization, [1]);

        $this->assertContains($variationEdge, $specialization->getGeneralizationEdges());
        $this->assertContains($variationEdge, $generalization->getSpecializationEdges());
    }
}
