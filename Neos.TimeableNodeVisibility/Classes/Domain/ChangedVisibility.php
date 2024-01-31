<?php

namespace Neos\TimeableNodeVisibility\Domain;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * @internal
 */
final class ChangedVisibility
{
    private function __construct(
        public readonly Node $node,
        public readonly ChangedVisibilityType $type
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
