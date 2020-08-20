<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\RelationDistributionStrategy;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\RelationDistributionStrategyIsInvalid;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;

/**
 * The "Move node aggregate" command
 *
 * In `contentStreamIdentifier`
 * and `dimensionSpacePoint`,
 * move node aggregate `nodeAggregateIdentifier`
 * into `newParentNodeAggregateIdentifier` (or keep the current parent)
 * between `newPrecedingSiblingNodeAggregateIdentifier`
 * and `newSucceedingSiblingNodeAggregateIdentifier` (or as last of all siblings)
 * using `relationDistributionStrategy`
 *
 * Why can you specify **both** newPrecedingSiblingNodeAggregateIdentifier and newSucceedingSiblingNodeAggregateIdentifier?
 * - it can happen that in one subgraph, only one of these match.
 * - See the PHPDoc of the attributes (a few lines down) for the exact behavior.
 */
final class MoveNodeAggregate implements \JsonSerializable, RebasableToOtherContentStreamsInterface, MatchableWithNodeAddressInterface
{
    /**
     * The content stream in which the move operation is to be performed
     *
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * This is one of the *covered* dimension space points of the node aggregate and not necessarily one of the occupied ones.
     * This allows us to move virtual specializations only when using the scatter strategy.
     *
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * The node aggregate to be moved
     *
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * This is the identifier of the new parent node aggregate.
     * If given, it enforces that all nodes in the given aggregate are moved into nodes of the parent aggregate,
     * even if the given siblings belong to other parents. In latter case, those siblings are ignored.
     *
     * @var NodeAggregateIdentifier
     */
    private $newParentNodeAggregateIdentifier;

    /**
     * This is the identifier of the new preceding sibling node aggregate.
     * If given and no successor found, it is attempted to insert the moved nodes right after nodes of this aggregate.
     * In dimension space points this aggregate does not cover, other siblings, in order of proximity, are tried to be used instead.
     *
     * @var NodeAggregateIdentifier|null
     */
    private $newPrecedingSiblingNodeAggregateIdentifier;

    /**
     * This is the identifier of the new succeeding sibling node aggregate.
     * If given, it is attempted to insert the moved nodes right before nodes of this aggregate.
     * In dimension space points this aggregate does not cover, the preceding sibling is tried to be used instead.
     *
     * @var NodeAggregateIdentifier|null
     */
    private $newSucceedingSiblingNodeAggregateIdentifier;

    /**
     * The relation distribution strategy to be used
     *
     * @var RelationDistributionStrategy
     */
    private $relationDistributionStrategy;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newParentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newPrecedingSiblingNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newSucceedingSiblingNodeAggregateIdentifier,
        RelationDistributionStrategy $relationDistributionStrategy
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newParentNodeAggregateIdentifier = $newParentNodeAggregateIdentifier;
        $this->newPrecedingSiblingNodeAggregateIdentifier = $newPrecedingSiblingNodeAggregateIdentifier;
        $this->newSucceedingSiblingNodeAggregateIdentifier = $newSucceedingSiblingNodeAggregateIdentifier;
        $this->relationDistributionStrategy = $relationDistributionStrategy;
    }

    /**
     * @param array $array
     * @return MoveNodeAggregate
     * @throws RelationDistributionStrategyIsInvalid
     */
    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            new DimensionSpacePoint($array['dimensionSpacePoint']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            isset($array['newParentNodeAggregateIdentifier']) ? NodeAggregateIdentifier::fromString($array['newParentNodeAggregateIdentifier']) : null,
            isset($array['newPrecedingSiblingNodeAggregateIdentifier']) ? NodeAggregateIdentifier::fromString($array['newPrecedingSiblingNodeAggregateIdentifier']) : null,
            isset($array['newSucceedingSiblingNodeAggregateIdentifier']) ? NodeAggregateIdentifier::fromString($array['newSucceedingSiblingNodeAggregateIdentifier']) : null,
            RelationDistributionStrategy::fromString($array['relationDistributionStrategy'])
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNewParentNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newParentNodeAggregateIdentifier;
    }

    public function getNewPrecedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newPrecedingSiblingNodeAggregateIdentifier;
    }

    public function getNewSucceedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newSucceedingSiblingNodeAggregateIdentifier;
    }

    public function getRelationDistributionStrategy(): RelationDistributionStrategy
    {
        return $this->relationDistributionStrategy;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'dimensionSpacePoint' => $this->dimensionSpacePoint,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'newParentNodeAggregateIdentifier' => $this->newParentNodeAggregateIdentifier,
            'newPrecedingSiblingNodeAggregateIdentifier' => $this->newPrecedingSiblingNodeAggregateIdentifier,
            'newSucceedingSiblingNodeAggregateIdentifier' => $this->newSucceedingSiblingNodeAggregateIdentifier,
            'relationDistributionStrategy' => $this->relationDistributionStrategy,
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new MoveNodeAggregate(
            $targetContentStreamIdentifier,
            $this->dimensionSpacePoint,
            $this->nodeAggregateIdentifier,
            $this->newParentNodeAggregateIdentifier,
            $this->newPrecedingSiblingNodeAggregateIdentifier,
            $this->newSucceedingSiblingNodeAggregateIdentifier,
            $this->relationDistributionStrategy
        );
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (string)$this->contentStreamIdentifier === (string)$nodeAddress->getContentStreamIdentifier()
            && $this->nodeAggregateIdentifier->equals($nodeAddress->getNodeAggregateIdentifier())
            && $this->dimensionSpacePoint->equals($nodeAddress->getDimensionSpacePoint());
    }
}
