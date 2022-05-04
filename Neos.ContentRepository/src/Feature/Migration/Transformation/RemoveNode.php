<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeDisabling\Command\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * Remove Node
 */
class RemoveNode implements NodeBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    private ?NodeVariantSelectionStrategy $strategy = null;

    private ?DimensionSpacePoint $overriddenDimensionSpacePoint = null;

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    public function setStrategy(string $strategy): void
    {
        $this->strategy = NodeVariantSelectionStrategy::from($strategy);
    }

    /**
     * @param array<string,string> $overriddenDimensionSpacePoint
     */
    public function setOverriddenDimensionSpacePoint(array $overriddenDimensionSpacePoint): void
    {
        $this->overriddenDimensionSpacePoint = DimensionSpacePoint::fromArray($overriddenDimensionSpacePoint);
    }

    /**
     * Remove the property from the given node.
     */
    public function execute(
        NodeInterface $node,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        if ($this->overriddenDimensionSpacePoint !== null && $this->strategy === null) {
            $this->strategy = NodeVariantSelectionStrategy::STRATEGY_ONLY_GIVEN_VARIANT;
        } elseif ($this->strategy === null) {
            $this->strategy = NodeVariantSelectionStrategy::STRATEGY_VIRTUAL_SPECIALIZATIONS;
        }

        if ($this->strategy === NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS) {
            throw new InvalidMigrationConfiguration(
                'For RemoveNode, the strategy allVariants is not supported, as this would lead to nodes'
                    . ' being deleted which might potentially not be matched by this filter.'
            );
        }

        $coveredDimensionSpacePoint = $this->overriddenDimensionSpacePoint
            ?: $node->getOriginDimensionSpacePoint()->toDimensionSpacePoint();

        if (!$coveredDimensionSpacePoints->contains($coveredDimensionSpacePoint)) {
            // we are currently in a Node which has other covered dimension space points than the target ones,
            // so we do not need to do anything.
            return CommandResult::createEmpty();
        }

        return $this->nodeAggregateCommandHandler->handleRemoveNodeAggregate(new RemoveNodeAggregate(
            $contentStreamForWriting,
            $node->getNodeAggregateIdentifier(),
            $coveredDimensionSpacePoint,
            $this->strategy,
            UserIdentifier::forSystemUser()
        ));
    }
}
