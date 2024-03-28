<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The content-repository-id content-stream-id dimension-space-point and the node-aggregate-id
 * Are used in combination to distinctly identify a single node.
 *
 * By using the content graph for the content repository
 * one can build a subgraph with the right perspective to find this node:
 *
 *      $subgraph = $contentGraph->getSubgraph(
 *          $identity->workspaceName,
 *          $nodeIdentity->dimensionSpacePoint,
 *          // resolve also all disabled nodes
 *          VisibilityConstraints::withoutRestrictions()
 *      );
 *      $node = $subgraph->findNodeById($nodeIdentity->nodeAggregateId);
 *
 * @api
 */
final readonly class NodeIdentity implements \JsonSerializable
{
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public WorkspaceName $workspaceName,
        public DimensionSpacePoint $dimensionSpacePoint,
        public NodeAggregateId $nodeAggregateId,
    ) {
    }

    public static function create(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return new self($contentRepositoryId, $workspaceName, $dimensionSpacePoint, $nodeAggregateId);
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
            NodeAggregateId::fromString($array['nodeAggregateId'])
        );
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true, JSON_THROW_ON_ERROR));
    }

    public function withNodeAggregateId(NodeAggregateId $nodeAggregateId): self
    {
        return new self($this->contentRepositoryId, $this->workspaceName, $this->dimensionSpacePoint, $nodeAggregateId);
    }

    public function equals(self $other): bool
    {
        return $this->contentRepositoryId->equals($other->contentRepositoryId)
            && $this->workspaceName->equals($other->workspaceName)
            && $this->dimensionSpacePoint->equals($other->dimensionSpacePoint)
            && $this->nodeAggregateId->equals($other->nodeAggregateId);
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'contentRepositoryId' => $this->contentRepositoryId,
            'workspaceName' => $this->workspaceName,
            'dimensionSpacePoint' => $this->dimensionSpacePoint,
            'nodeAggregateId' => $this->nodeAggregateId
        ];
    }
}
