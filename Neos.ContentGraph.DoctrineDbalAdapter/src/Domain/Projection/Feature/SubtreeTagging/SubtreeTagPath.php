<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;

/**
 * @internal
 */
final readonly class SubtreeTagPath
{
    private function __construct()
    {
    }

    public static function create(SubtreeTag $subtreeTag): string
    {
        return '$."' . $subtreeTag->value . '"';
    }
}
