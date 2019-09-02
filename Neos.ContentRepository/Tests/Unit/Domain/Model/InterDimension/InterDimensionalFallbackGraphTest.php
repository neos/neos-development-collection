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
use Neos\Utility\ObjectAccess;

/**
 * Test cases for the inter dimensional fallback graph
 */
class InterDimensionalFallbackGraphTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createContentSubgraphRegistersSubgraph()
    {
        $graph = new InterDimension\InterDimensionalFallbackGraph([]);

        $contentSubgraph = $graph->createContentSubgraph(['test' => new IntraDimension\ContentDimensionValue('a')]);

        self::assertSame($contentSubgraph, $graph->getSubgraph($contentSubgraph->getIdentityHash()));
    }

    /**
     * @test
     */
    public function connectSubgraphsAddsFallbackToVariant()
    {
        $contentDimension = new IntraDimension\ContentDimension('test');
        $fallbackValue = $contentDimension->createValue('a');
        $variantValue = $contentDimension->createValue('b', $fallbackValue);
        $graph = new InterDimension\InterDimensionalFallbackGraph([$contentDimension]);

        $fallback = new InterDimension\ContentSubgraph(['test' => $fallbackValue]);
        $variant = new InterDimension\ContentSubgraph(['test' => $variantValue]);

        $graph->connectSubgraphs($variant, $fallback);

        self::assertContains($fallback, $variant->getFallback());
    }

    /**
     * @test
     */
    public function connectSubgraphsAddsVariantToFallback()
    {
        $contentDimension = new IntraDimension\ContentDimension('test');
        $fallbackValue = $contentDimension->createValue('a');
        $variantValue = $contentDimension->createValue('b', $fallbackValue);
        $graph = new InterDimension\InterDimensionalFallbackGraph([$contentDimension]);

        $fallback = new InterDimension\ContentSubgraph(['test' => $fallbackValue]);
        $variant = new InterDimension\ContentSubgraph(['test' => $variantValue]);

        $graph->connectSubgraphs($variant, $fallback);

        self::assertContains($variant, $fallback->getVariants());
    }

    /**
     * @test
     * @dataProvider dimensionValueCombinationProvider
     * @param array $variantDimensionCombination
     * @param array $fallbackDimensionCombination
     * @param array $expectedWeight
     */
    public function calculateFallbackWeightAggregatesCorrectWeightPerDimension(array $variantDimensionCombination, array $fallbackDimensionCombination, array $expectedWeight)
    {
        $intraGraph = new IntraDimension\IntraDimensionalFallbackGraph();

        $availableDimensionValues = [];

        $primaryDimension = $intraGraph->createDimension('primary');
        $availableDimensionValues['primary0'] = $primaryDimension->createValue('0');
        $availableDimensionValues['primary1'] = $primaryDimension->createValue('1', $availableDimensionValues['primary0']);
        $availableDimensionValues['primary2'] = $primaryDimension->createValue('2', $availableDimensionValues['primary1']);

        $secondaryDimension = $intraGraph->createDimension('secondary');
        $availableDimensionValues['secondary0a'] = $secondaryDimension->createValue('0a');
        $availableDimensionValues['secondary0b'] = $secondaryDimension->createValue('0b');

        $tertiaryDimension = $intraGraph->createDimension('tertiary');
        $availableDimensionValues['tertiary0'] = $tertiaryDimension->createValue('0');
        $availableDimensionValues['tertiary1a'] = $tertiaryDimension->createValue('1a', $availableDimensionValues['tertiary0']);
        $availableDimensionValues['tertiary1b'] = $tertiaryDimension->createValue('1b', $availableDimensionValues['tertiary0']);

        $interGraph = new InterDimension\InterDimensionalFallbackGraph([$primaryDimension, $secondaryDimension, $tertiaryDimension]);

        array_walk($variantDimensionCombination, function (&$value) use ($availableDimensionValues) {
            $value = $availableDimensionValues[$value];
        });
        $variantContentSubgraph = new InterDimension\ContentSubgraph($variantDimensionCombination);

        array_walk($fallbackDimensionCombination, function (&$value) use ($availableDimensionValues) {
            $value = $availableDimensionValues[$value];
        });
        $fallbackContentSubgraph = new InterDimension\ContentSubgraph($fallbackDimensionCombination);

        self::assertSame($expectedWeight, $interGraph->calculateFallbackWeight($variantContentSubgraph, $fallbackContentSubgraph));
    }

    public function dimensionValueCombinationProvider()
    {
        return [
            [
                ['primary' => 'primary2', 'secondary' => 'secondary0a', 'tertiary' => 'tertiary1a'],
                ['primary' => 'primary0', 'secondary' => 'secondary0a', 'tertiary' => 'tertiary0'],
                ['primary' => 2, 'secondary' => 0, 'tertiary' => 1]
            ],
            [
                ['primary' => 'primary1', 'secondary' => 'secondary0b', 'tertiary' => 'tertiary0'],
                ['primary' => 'primary0', 'secondary' => 'secondary0b', 'tertiary' => 'tertiary0'],
                ['primary' => 1, 'secondary' => 0, 'tertiary' => 0]
            ],
            [
                ['primary' => 'primary0', 'secondary' => 'secondary0b', 'tertiary' => 'tertiary1b'],
                ['primary' => 'primary0', 'secondary' => 'secondary0b', 'tertiary' => 'tertiary0'],
                ['primary' => 0, 'secondary' => 0, 'tertiary' => 1]
            ]
        ];
    }

    /**
     * @test
     */
    public function determineWeightNormalizationBaseEvaluatesToMaximumDimensionDepthPlusOne()
    {
        $firstDimension = new IntraDimension\ContentDimension('first');
        $firstDepth = random_int(0, 100);
        ObjectAccess::setProperty($firstDimension, 'depth', $firstDepth, true);

        $secondDimension = new IntraDimension\ContentDimension('second');
        $secondDepth = random_int(0, 100);
        ObjectAccess::setProperty($secondDimension, 'depth', $secondDepth, true);

        $graph = new InterDimension\InterDimensionalFallbackGraph([$firstDimension, $secondDimension]);
        self::assertSame(max($firstDepth, $secondDepth) + 1, $graph->determineWeightNormalizationBase());
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
        $primaryDimension = new IntraDimension\ContentDimension('primary');
        ObjectAccess::setProperty($primaryDimension, 'depth', $dimensionDepth, true);
        $secondaryDimension = new IntraDimension\ContentDimension('secondary');
        ObjectAccess::setProperty($secondaryDimension, 'depth', $dimensionDepth, true);
        $tertiaryDimension = new IntraDimension\ContentDimension('tertiary');
        ObjectAccess::setProperty($tertiaryDimension, 'depth', $dimensionDepth, true);

        $graph = new InterDimension\InterDimensionalFallbackGraph([$primaryDimension, $secondaryDimension, $tertiaryDimension]);

        $variant = new InterDimension\ContentSubgraph([]);
        $fallback = new InterDimension\ContentSubgraph([]);
        $variationEdge = new InterDimension\VariationEdge($variant, $fallback, $weight);

        self::assertSame($expectedNormalizedWeight, $graph->normalizeWeight($variationEdge->getWeight()));
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
     * @dataProvider fallbackPrioritizationProvider
     * @param array $primaryFallbackWeight
     * @param array $secondaryFallbackWeight
     */
    public function getPrimaryFallbackReturnsFallbackWithLowestNormalizedWeight($primaryFallbackWeight, $secondaryFallbackWeight)
    {
        $primaryDimension = new IntraDimension\ContentDimension('primary');
        $primaryVariantValue = $primaryDimension->createValue('variant');
        $primaryDummyValue1 = $primaryDimension->createValue('dummy1');
        $primaryDummyValue2 = $primaryDimension->createValue('dummy2');
        ObjectAccess::setProperty($primaryDimension, 'depth', 5, true);
        $secondaryDimension = new IntraDimension\ContentDimension('secondary');
        $secondaryDummyValue = $secondaryDimension->createValue('dummy');
        ObjectAccess::setProperty($secondaryDimension, 'depth', 5, true);
        $tertiaryDimension = new IntraDimension\ContentDimension('tertiary');
        $tertiaryDummyValue = $tertiaryDimension->createValue('dummy');
        ObjectAccess::setProperty($tertiaryDimension, 'depth', 5, true);

        $graph = new InterDimension\InterDimensionalFallbackGraph([$primaryDimension, $secondaryDimension, $tertiaryDimension]);

        $variant = new InterDimension\ContentSubgraph([
            'primary' => $primaryVariantValue,
            'secondary' => $secondaryDummyValue,
            'tertiary' => $tertiaryDummyValue
        ]);
        $primaryFallback = new InterDimension\ContentSubgraph([
            'primary' => $primaryDummyValue1,
            'secondary' => $secondaryDummyValue,
            'tertiary' => $tertiaryDummyValue
        ]);
        $secondaryFallback = new InterDimension\ContentSubgraph([
            'primary' => $primaryDummyValue2,
            'secondary' => $secondaryDummyValue,
            'tertiary' => $tertiaryDummyValue
        ]);
        new InterDimension\VariationEdge($variant, $primaryFallback, $primaryFallbackWeight);
        new InterDimension\VariationEdge($variant, $secondaryFallback, $secondaryFallbackWeight);

        self::assertSame($primaryFallback, $graph->getPrimaryFallback($variant));
    }

    public function fallbackPrioritizationProvider()
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
}
