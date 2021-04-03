<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Migration\Transformations;
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
use Neos\ContentRepository\Intermediary\Migration\Exception\InvalidMigrationConfiguration;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantSelectionStrategyIdentifier;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * Remove Node
 */
class RemoveNode implements NodeBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    /**
     * @var NodeVariantSelectionStrategyIdentifier|null
     */
    private ?NodeVariantSelectionStrategyIdentifier $strategy = null;

    /**
     * @var DimensionSpacePoint|null
     */
    private ?DimensionSpacePoint $overriddenDimensionSpacePoint = null;

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    /**
     * @param string $strategy
     */
    public function setStrategy(string $strategy): void
    {
        $this->strategy = NodeVariantSelectionStrategyIdentifier::fromString($strategy);
    }

    /**
     * @param string $overriddenDimensionSpacePoint
     */
    public function setOverriddenDimensionSpacePoint(array $overriddenDimensionSpacePoint): void
    {
        $this->overriddenDimensionSpacePoint = DimensionSpacePoint::fromArray($overriddenDimensionSpacePoint);
    }

    /**
     * Remove the property from the given node.
     *
     * @param NodeInterface $node
     * @param ContentStreamIdentifier $contentStreamForWriting
     * @return CommandResult
     */
    public function execute(NodeInterface $node, DimensionSpacePointSet $coveredDimensionSpacePoints, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        if ($this->overriddenDimensionSpacePoint !== null && $this->strategy === null) {
            $this->strategy = NodeVariantSelectionStrategyIdentifier::onlyGivenVariant();
        } elseif ($this->strategy === null) {
            $this->strategy = NodeVariantSelectionStrategyIdentifier::virtualSpecializations();
        }

        if ($this->strategy->equals(NodeVariantSelectionStrategyIdentifier::allVariants())) {
            throw new InvalidMigrationConfiguration('For RemoveNode, the strategy allVariants is not supported, as this would lead to nodes being deleted which might potentially not be matched by this filter.');
        }

        $coveredDimensionSpacePoint = $this->overriddenDimensionSpacePoint ?? $node->getOriginDimensionSpacePoint();

        return $this->nodeAggregateCommandHandler->handleRemoveNodeAggregate(new RemoveNodeAggregate(
            $contentStreamForWriting,
            $node->getNodeAggregateIdentifier(),
            $coveredDimensionSpacePoint,
            $this->strategy,
            UserIdentifier::forSystemUser()
        ));
    }
}
