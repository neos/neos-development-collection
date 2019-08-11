<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model\InterDimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\InterDimension;
use Neos\ContentRepository\Domain\Model\IntraDimension;
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
        $variant = new InterDimension\ContentSubgraph(['test' => new IntraDimension\ContentDimensionValue('a')]);
        $fallback = new InterDimension\ContentSubgraph(['test' => new IntraDimension\ContentDimensionValue('b')]);

        $variationEdge = new InterDimension\VariationEdge($variant, $fallback, [1]);

        self::assertContains($variationEdge, $variant->getFallbackEdges());
        self::assertContains($variationEdge, $fallback->getVariantEdges());
    }
}
