<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command;

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;

/**
 * Create a named reference from source to destination node
 */
final class SetNodeReferences implements \JsonSerializable, RebasableToOtherContentStreamsInterface, MatchableWithNodeAddressInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $sourceNodeAggregateIdentifier;

    /**
     * @var OriginDimensionSpacePoint
     */
    private $sourceOriginDimensionSpacePoint;

    /**
     * @var NodeAggregateIdentifier[]
     */
    private $destinationNodeAggregateIdentifiers;

    /**
     * @var PropertyName
     */
    private $referenceName;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $sourceNodeAggregateIdentifier
     * @param OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint
     * @param NodeAggregateIdentifier[] $destinationNodeAggregateIdentifiers
     * @param PropertyName $referenceName
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $sourceNodeAggregateIdentifier,
        OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        array $destinationNodeAggregateIdentifiers,
        PropertyName $referenceName
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->sourceNodeAggregateIdentifier = $sourceNodeAggregateIdentifier;
        $this->sourceOriginDimensionSpacePoint = $sourceOriginDimensionSpacePoint;
        $this->destinationNodeAggregateIdentifiers = $destinationNodeAggregateIdentifiers;
        $this->referenceName = $referenceName;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['sourceNodeAggregateIdentifier']),
            new OriginDimensionSpacePoint($array['sourceOriginDimensionSpacePoint']),
            array_map(function ($identifier) {
                return NodeAggregateIdentifier::fromString($identifier);
            }, $array['destinationNodeAggregateIdentifiers']),
            PropertyName::fromString($array['referenceName'])
        );
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getSourceNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->sourceNodeAggregateIdentifier;
    }

    /**
     * @return OriginDimensionSpacePoint
     */
    public function getSourceOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->sourceOriginDimensionSpacePoint;
    }

    /**
     * @return NodeAggregateIdentifier[]
     */
    public function getDestinationNodeAggregateIdentifiers(): array
    {
        return $this->destinationNodeAggregateIdentifiers;
    }

    /**
     * @return PropertyName
     */
    public function getReferenceName(): PropertyName
    {
        return $this->referenceName;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'sourceNodeAggregateIdentifier' => $this->sourceNodeAggregateIdentifier,
            'sourceOriginDimensionSpacePoint' => $this->sourceOriginDimensionSpacePoint,
            'destinationNodeAggregateIdentifiers' => $this->destinationNodeAggregateIdentifiers,
            'referenceName' => $this->referenceName
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new SetNodeReferences(
            $targetContentStreamIdentifier,
            $this->sourceNodeAggregateIdentifier,
            $this->sourceOriginDimensionSpacePoint,
            $this->destinationNodeAggregateIdentifiers,
            $this->referenceName
        );
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (
            $this->getContentStreamIdentifier()->equals($nodeAddress->getContentStreamIdentifier())
                && $this->getSourceOriginDimensionSpacePoint()->equals($nodeAddress->getDimensionSpacePoint())
                && $this->getSourceNodeAggregateIdentifier()->equals($nodeAddress->getNodeAggregateIdentifier())
        );
    }
}
