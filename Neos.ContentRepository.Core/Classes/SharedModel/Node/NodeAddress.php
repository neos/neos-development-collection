<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * This describes a node's read model identity namely:
 *
 * - {@see ContentRepositoryId}
 * - {@see WorkspaceName}
 * - {@see DimensionSpacePoint} (not to be confused with the {@see Node::$originDimensionSpacePoint})
 * - {@see NodeAggregateId}
 *
 * In combination the parts can be used to distinctly identify a single node.
 *
 * By using the content graph for the content repository
 * one can build a subgraph with the right perspective to find this node:
 *
 *      $subgraph = $contentRepository->getContentGraph($nodeAddress->workspaceName)->getSubgraph(
 *          $nodeAddress->dimensionSpacePoint,
 *          VisibilityConstraints::withoutRestrictions()
 *      );
 *      $node = $subgraph->findNodeById($nodeAddress->aggregateId);
 *
 * @api
 */
final readonly class NodeAddress implements \JsonSerializable
{
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public WorkspaceName $workspaceName,
        public DimensionSpacePoint $dimensionSpacePoint,
        public NodeAggregateId $aggregateId,
    ) {
    }

    public static function create(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $aggregateId,
    ): self {
        return new self($contentRepositoryId, $workspaceName, $dimensionSpacePoint, $aggregateId);
    }

    public static function fromNode(Node $node): self
    {
        return new self(
            $node->contentRepositoryId,
            $node->workspaceName,
            $node->dimensionSpacePoint,
            $node->aggregateId
        );
    }

    /**
     * @param array<string, mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentRepositoryId::fromString($array['contentRepositoryId']),
            WorkspaceName::fromString($array['workspaceName']),
            DimensionSpacePoint::fromArray($array['dimensionSpacePoint']),
            NodeAggregateId::fromString($array['aggregateId'])
        );
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true, JSON_THROW_ON_ERROR));
    }

    public function withAggregateId(NodeAggregateId $aggregateId): self
    {
        return new self($this->contentRepositoryId, $this->workspaceName, $this->dimensionSpacePoint, $aggregateId);
    }

    public function equals(self $other): bool
    {
        return $this->contentRepositoryId->equals($other->contentRepositoryId)
            && $this->workspaceName->equals($other->workspaceName)
            && $this->dimensionSpacePoint->equals($other->dimensionSpacePoint)
            && $this->aggregateId->equals($other->aggregateId);
    }

    public function toJson(): string
    {
        try {
            return json_encode($this, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-encode NodeAddress: %s', $e->getMessage()), 1715608338, $e);
        }
    }

    public static function fromUriString(string $encoded): self
    {
        $parts = explode('__', $encoded);
        if (count($parts) !== 4) {
            throw new \RuntimeException(sprintf('Failed to decode NodeAddress from uri string: %s', $encoded), 1716051847);
        }
        return new self(
            ContentRepositoryId::fromString($parts[0]),
            WorkspaceName::fromString($parts[1]),
            DimensionSpacePoint::fromUriRepresentation($parts[2]),
            NodeAggregateId::fromString($parts[3])
        );
    }

    public function toUriString(): string
    {
        return sprintf(
            '%s__%s__%s__%s',
            $this->contentRepositoryId->value,
            $this->workspaceName->value,
            $this->dimensionSpacePoint->toUriRepresentation(),
            $this->aggregateId->value
        );
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
