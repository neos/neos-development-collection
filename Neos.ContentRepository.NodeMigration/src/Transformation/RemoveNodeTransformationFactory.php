<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * Remove Node
 */
class RemoveNodeTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        $strategy = null;
        if (isset($settings['strategy'])) {
            $strategy = NodeVariantSelectionStrategy::from($settings['strategy']);
        }

        $overriddenDimensionSpacePoint = null;
        if (isset($settings['overriddenDimensionSpacePoint'])) {
            $overriddenDimensionSpacePoint = DimensionSpacePoint::fromArray($settings['overriddenDimensionSpacePoint']);
        }

        return new class (
            $strategy,
            $overriddenDimensionSpacePoint,
            $contentRepository
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                private ?NodeVariantSelectionStrategy $strategy,
                private readonly ?DimensionSpacePoint $overriddenDimensionSpacePoint,
                private readonly ContentRepository $contentRepository
            ) {
            }

            /**
             * Remove the property from the given node.
             */
            public function execute(
                Node $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                ContentStreamId $contentStreamForWriting
            ): ?CommandResult {
                if ($this->strategy === null) {
                    $this->strategy = NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS;
                }

                if ($this->strategy === NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS) {
                    throw new InvalidMigrationConfiguration(
                        'For RemoveNode, the strategy allVariants is not supported, as this would lead to nodes'
                        . ' being deleted which might potentially not be matched by this filter.'
                    );
                }

                $coveredDimensionSpacePoint = $this->overriddenDimensionSpacePoint
                    ?: $node->originDimensionSpacePoint->toDimensionSpacePoint();

                if (!$coveredDimensionSpacePoints->contains($coveredDimensionSpacePoint)) {
                    // we are currently in a Node which has other covered dimension space points than the target ones,
                    // so we do not need to do anything.
                    return null;
                }

                return $this->contentRepository->handle(RemoveNodeAggregate::create(
                    $contentStreamForWriting,
                    $node->nodeAggregateId,
                    $coveredDimensionSpacePoint,
                    $this->strategy,
                ));
            }
        };
    }
}
