<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

/**
 * Create a named reference from source- to destination-node
 */
final class SetNodeReferences implements \JsonSerializable
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var NodeAggregateIdentifier[]
     */
    private $destinationNodeAggregateIdentifiers;

    /**
     * @var PropertyName
     */
    private $propertyName;

    /**
     * CreateReferenceBetweenNodes constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param PropertyName $propertyName
     * @param array $destinationNodeAggregateIdentifiers
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier, PropertyName $propertyName, array $destinationNodeAggregateIdentifiers) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->propertyName = $propertyName;
        $this->destinationNodeAggregateIdentifiers = $destinationNodeAggregateIdentifiers;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeIdentifier::fromString($array['nodeIdentifier']),
            PropertyName::fromString($array['propertyName']),
            array_map(function($identifier) { return NodeAggregateIdentifier::fromString($identifier); }, $array['destinationNodeAggregateIdentifiers'])
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
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
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
    public function getPropertyName(): PropertyName
    {
        return $this->propertyName;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeIdentifier' => $this->nodeIdentifier,
            'propertyName' => $this->propertyName,
            'destinationNodeAggregateIdentifiers' => $this->destinationNodeAggregateIdentifiers,
        ];
    }
}
