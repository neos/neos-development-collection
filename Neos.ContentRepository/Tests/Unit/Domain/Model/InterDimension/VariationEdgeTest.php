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
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Domain\Model\InterDimension\ContentSubgraph;
use Neos\ContentRepository\Domain\Model\IntraDimension\ContentDimensionValue;
use Neos\ContentRepository\Domain\Model\InterDimension\VariationEdge;
use Neos\ContentRepository\Domain\Model\InterDimension;
use Neos\ContentRepository\Domain\Model\IntraDimension;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for variation edges
 */
class VariationEdgeTest extends UnitTestCase
{
    #[Test]
    public function variationEdgesAreRegisteredInFallbackAndVariantUponCreation()
    {
        $variant = new ContentSubgraph(['test' => new ContentDimensionValue('a')]);
        $fallback = new ContentSubgraph(['test' => new ContentDimensionValue('b')]);

        $variationEdge = new VariationEdge($variant, $fallback, [1]);

        self::assertContains($variationEdge, $variant->getFallbackEdges());
        self::assertContains($variationEdge, $fallback->getVariantEdges());
    }
}
