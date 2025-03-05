<?php
declare(strict_types=1);

namespace Neos\Neos\Presentation;

use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;

final readonly class NodeAggregateIdPath implements \JsonSerializable
{
    private function __construct(
        private NodeAggregateIds $nodeAggregateIds
    ) {
    }

    public static function create(NodeAggregateIds $nodeAggregateIds): self
    {
        return new self($nodeAggregateIds);
    }

    public static function fromNodes(Nodes $nodes): self
    {
        return new self(NodeAggregateIds::fromNodes($nodes));
    }

    public function serializeToString(): string
    {
        return '/' . join('/', $this->nodeAggregateIds->toStringArray());
    }

    public function jsonSerialize(): string
    {
        return $this->serializeToString();
    }

    public function __toString(): string
    {
        return $this->serializeToString();
    }
}
