<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;

/**
 * Create a named reference from source to destination node
 */
final class SetNodeReferences implements \JsonSerializable, CopyableAcrossContentStreamsInterface, MatchableWithNodeAddressInterface
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
     * @var DimensionSpacePoint
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
     * @param DimensionSpacePoint $sourceOriginDimensionSpacePoint
     * @param NodeAggregateIdentifier[] $destinationNodeAggregateIdentifiers
     * @param PropertyName $referenceName
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $sourceNodeAggregateIdentifier,
        DimensionSpacePoint $sourceOriginDimensionSpacePoint,
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
            new DimensionSpacePoint($array['sourceOriginDimensionSpacePoint']),
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
     * @return DimensionSpacePoint
     */
    public function getSourceOriginDimensionSpacePoint(): DimensionSpacePoint
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

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifierIdentifier): self
    {
        return new SetNodeReferences(
            $targetContentStreamIdentifierIdentifier,
            $this->sourceNodeAggregateIdentifier,
            $this->sourceOriginDimensionSpacePoint,
            $this->destinationNodeAggregateIdentifiers,
            $this->referenceName
        );
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (
            (string)$this->getContentStreamIdentifier() === (string)$nodeAddress->getContentStreamIdentifier()
                && (string)$this->getSourceOriginDimensionSpacePoint() === (string)$nodeAddress->getDimensionSpacePoint()
                && (string)$this->getSourceNodeAggregateIdentifier() === (string)$nodeAddress->getNodeAggregateIdentifier()
        );
    }
}
