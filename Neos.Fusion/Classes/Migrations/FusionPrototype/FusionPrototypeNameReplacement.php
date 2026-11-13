<?php

declare(strict_types=1);

namespace Neos\Fusion\Migrations\FusionPrototype;

use Neos\Flow\Annotations as Flow;

/**
 * @internal
 */
#[Flow\Proxy(false)]
final class FusionPrototypeNameReplacement
{
    public function __construct(
        public readonly string $oldName,
        public readonly string $newName,
        public readonly string $comment,
        public readonly bool $skipPrototypeDefinitions = false,
    ) {
    }
}
