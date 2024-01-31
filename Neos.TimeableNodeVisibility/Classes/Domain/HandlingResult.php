<?php

namespace Neos\TimeableNodeVisibility\Domain;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

final class HandlingResult
{
    private function __construct(
        public readonly Node $node,
        public readonly HandlingResultType $type
    ) {
    }

    public static function createWithEnabled(Node $node): self
    {
        return new self($node, HandlingResultType::ENABLED);
    }

    public static function createWithDisabled(Node $node): self
    {
        return new self($node, HandlingResultType::DISABLED);
    }
}
