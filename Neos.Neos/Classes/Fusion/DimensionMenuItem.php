<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * A menu item for dimension menus
 * Compared to the default {@see MenuItem} it has no `menuLevel` property, but one for the `targetDimensions`
 */
final readonly class DimensionMenuItem
{
    /**
     * @param array<string,mixed>|null $targetDimensions
     */
    public function __construct(
        public ?Node $node,
        public ?MenuItemState $state = null,
        public ?string $label = null,
        public ?array $targetDimensions = null,
        public ?string $uri = null
    ) {
    }
}
