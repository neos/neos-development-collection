<?php

declare(strict_types=1);

namespace Neos\TimeableNodeVisibility\Domain;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * @internal
 */
final readonly class ChangedVisibility
{
    private function __construct(
        public Node $node,
        public ChangedVisibilityType $type,
        public ?string $message = null
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

    public static function createForError(Node $node, string $message): self
    {
        return new self($node, ChangedVisibilityType::ERROR, $message);
    }

    public function isError(): bool
    {
        return $this->type === ChangedVisibilityType::ERROR;
    }
}
