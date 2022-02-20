<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Migration\Transformations;

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
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Migration\Exception\InvalidMigrationConfiguration;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantSelectionStrategyIdentifier;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * Remove Node
 */
class RemoveNode implements NodeBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    private ?NodeVariantSelectionStrategyIdentifier $strategy = null;

    private ?DimensionSpacePoint $overriddenDimensionSpacePoint = null;

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    public function setStrategy(string $strategy): void
    {
        $this->strategy = NodeVariantSelectionStrategyIdentifier::from($strategy);
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
            $this->strategy = NodeVariantSelectionStrategyIdentifier::STRATEGY_ONLY_GIVEN_VARIANT;
        } elseif ($this->strategy === null) {
            $this->strategy = NodeVariantSelectionStrategyIdentifier::STRATEGY_VIRTUAL_SPECIALIZATIONS;
        }

        if ($this->strategy === NodeVariantSelectionStrategyIdentifier::STRATEGY_ALL_VARIANTS) {
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

        return $this->nodeAggregateCommandHandler->handleRemoveNodeAggregate(RemoveNodeAggregate::create(
            $contentStreamForWriting,
            $node->getNodeAggregateIdentifier(),
            $coveredDimensionSpacePoint,
            $this->strategy,
            UserIdentifier::forSystemUser()
        ));
    }
}
