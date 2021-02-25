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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantSelectionStrategyIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * Enable the given node aggregate in the given content stream in a dimension space point using a given strategy
 *
 * @Flow\Proxy(false)
 */
final class EnableNodeAggregate implements \JsonSerializable, RebasableToOtherContentStreamsInterface, MatchableWithNodeAddressInterface
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    /**
     * Node aggregate identifier of the node the user intends to enable
     */
    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    /**
     * The covered dimension space points of the node aggregate in which the user intends to enable it
     */
    private DimensionSpacePoint $coveredDimensionSpacePoint;

    /**
     * The strategy the user chose to determine which specialization variants will also be disabled
     */
    private NodeVariantSelectionStrategyIdentifier $nodeVariantSelectionStrategy;

    private UserIdentifier $initiatingUserIdentifier;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeVariantSelectionStrategyIdentifier $nodeVariantSelectionStrategy,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->coveredDimensionSpacePoint = $coveredDimensionSpacePoint;
        $this->nodeVariantSelectionStrategy = $nodeVariantSelectionStrategy;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            new DimensionSpacePoint($array['coveredDimensionSpacePoint']),
            NodeVariantSelectionStrategyIdentifier::fromString($array['nodeVariantSelectionStrategy']),
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getCoveredDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->coveredDimensionSpacePoint;
    }

    public function getNodeVariantSelectionStrategy(): NodeVariantSelectionStrategyIdentifier
    {
        return $this->nodeVariantSelectionStrategy;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
            'nodeVariantSelectionStrategy' => $this->nodeVariantSelectionStrategy,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new EnableNodeAggregate(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->coveredDimensionSpacePoint,
            $this->nodeVariantSelectionStrategy,
            $this->initiatingUserIdentifier
        );
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (
            (string)$this->getContentStreamIdentifier() === (string)$nodeAddress->getContentStreamIdentifier()
            && $this->getCoveredDimensionSpacePoint()->equals($nodeAddress->getDimensionSpacePoint())
            && $this->getNodeAggregateIdentifier()->equals($nodeAddress->getNodeAggregateIdentifier())
        );
    }
}
