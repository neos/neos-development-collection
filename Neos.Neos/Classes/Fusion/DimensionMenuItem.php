<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * A menu item
 */
final class DimensionMenuItem
{
    /**
     * @param array<string,mixed>|null $targetDimensions
     */
    public function __construct(
        public readonly ?Node $node,
        public readonly ?MenuItemState $state = null,
        public readonly ?string $label = null,
        public readonly ?array $targetDimensions = null,
        public readonly ?string $uri = null
    ) {
    }
}
