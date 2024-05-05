<?php

namespace Neos\TimeableNodeVisibility\Domain;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * @internal
 */
final readonly class ChangedVisibility
{
    private function __construct(
        public Node $node,
        public ChangedVisibilityType $type
    ) {
    }

    public static function createForNodeWasEnabled(Node $node): self
    {
        return new self($node, ChangedVisibilityType::NODE_WAS_ENABLED);
    }

    public static function createForNodeWasDisabled(Node $node): self
    {
        return new self($node, ChangedVisibilityType::NODE_WAS_DISABLED);
    }
}
