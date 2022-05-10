<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Filter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\VariantType;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Projection\Content\NodeInterface;

/**
 * Filter nodes by origin dimension space point. Normally, check for exact matches; but if includeSpecializations=TRUE,
 * we also match dimension space points "underneath" the given dimension space point.
 *
 * So if the following DimensionSpacePoints exist:
 *
 *      DE    EN
 *      |
 *     CH
 *
 * ...and you check for "language=DE", then ONLY the node with originDimensionSpacePoint language=DE will match.
 * ...and you check for "language=DE" and includeSpecializations=TRUE,
 *    then the nodes with originDimensionSpacePoint language=DE and language=CH will match.
 */
class DimensionSpacePointsFilterFactory implements FilterFactoryInterface
{
    public function __construct(private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph)
    {
    }

    /**
     * @param array<string,mixed> $settings
     */
    public function build(array $settings): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface
    {
        $points = OriginDimensionSpacePointSet::fromArray($settings['points']);

        $includeSpecializations = false;
        if (isset($settings['includeSpecializations'])) {
            $includeSpecializations = (bool)$settings['includeSpecializations'];
        }

        return new class(
            $points,
            $includeSpecializations,
            $this->interDimensionalVariationGraph
        ) implements NodeBasedFilterInterface {
            public function __construct(
                private readonly OriginDimensionSpacePointSet $points,
                private readonly bool $includeSpecializations,
                private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph
            ) {
            }

            public function matches(NodeInterface $node): bool
            {
                if ($this->includeSpecializations) {
                    foreach ($this->points as $point) {
                        $variantType = $this->interDimensionalVariationGraph->getVariantType(
                            $node->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
                            $point->toDimensionSpacePoint()
                        );
                        if ($variantType === VariantType::TYPE_SAME
                            || $variantType === VariantType::TYPE_SPECIALIZATION) {
                            // this is true if the node is a specialization of $point (or if they are equal)
                            return true;
                        }
                    }
                    return false;
                } else {
                    // exact matches on $this->points
                    return $this->points->contains($node->getOriginDimensionSpacePoint());
                }
            }
        };
    }
}
