<?php

namespace Neos\TimeableNodeVisibility\Domain;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

final class HandlingResult
{
    public const RESULT_ENABLED = 'ENABLED';
    public const RESULT_DISABLED = 'DISABLED';

    private function __construct(
        public readonly Node $node,
        public readonly string $result,
    ) {
    }

    public static function createWithEnabled(Node $node): static
    {
        return new static($node, static::RESULT_ENABLED);
    }

    public static function createWithDisabled(Node $node): static
    {
        return new static($node, static::RESULT_DISABLED);
    }
}
