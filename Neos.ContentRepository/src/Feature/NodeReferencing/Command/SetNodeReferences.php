<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\NodeReferencing\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\Feature\Common\NodeIdentifierToPublishOrDiscard;
use Neos\ContentRepository\Feature\Common\NodeReferencesToWrite;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeIdentifierToPublishOrDiscardInterface;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * Create a named reference from source to destination node
 *
 * @api commands are the write-API of the ContentRepository
 */
final class SetNodeReferences implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdentifierToPublishOrDiscardInterface
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $sourceNodeAggregateIdentifier,
        public readonly OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        public readonly PropertyName $referenceName,
        public readonly NodeReferencesToWrite $references,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['sourceNodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromArray($array['sourceOriginDimensionSpacePoint']),
            PropertyName::fromString($array['referenceName']),
            NodeReferencesToWrite::fromArray($array['references']),
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'sourceNodeAggregateIdentifier' => $this->sourceNodeAggregateIdentifier,
            'sourceOriginDimensionSpacePoint' => $this->sourceOriginDimensionSpacePoint,
            'referenceName' => $this->referenceName,
            'references' => $this->references,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $target): self
    {
        return new self(
            $target,
            $this->sourceNodeAggregateIdentifier,
            $this->sourceOriginDimensionSpacePoint,
            $this->referenceName,
            $this->references,
            $this->initiatingUserIdentifier
        );
    }

    public function matchesNodeIdentifier(NodeIdentifierToPublishOrDiscard $nodeIdentifierToPublish): bool
    {
        return (
            $this->contentStreamIdentifier === $nodeIdentifierToPublish->contentStreamIdentifier
                && $this->sourceOriginDimensionSpacePoint->equals($nodeIdentifierToPublish->dimensionSpacePoint)
                && $this->sourceNodeAggregateIdentifier->equals($nodeIdentifierToPublish->nodeAggregateIdentifier)
        );
    }
}
