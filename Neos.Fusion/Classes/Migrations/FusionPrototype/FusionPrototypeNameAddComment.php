<?php

declare(strict_types=1);

namespace Neos\Fusion\Migrations\FusionPrototype;

use Neos\Flow\Annotations as Flow;

/**
 * @internal
 */
#[Flow\Proxy(false)]
final class FusionPrototypeNameAddComment
{
    public function __construct(
        public readonly string $name,
        public readonly string $comment,
    ) {
    }
}
