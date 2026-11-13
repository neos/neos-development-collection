<?php

namespace Neos\Fusion\Migrations\EelExpression;

use Neos\Flow\Annotations as Flow;

/**
 * @internal
 */
#[Flow\Proxy(false)]
final class AfxExpressionPosition
{
    public function __construct(
        public readonly string $code,
        public readonly int $fromOffset,
        public readonly int $toOffset
    ) {
    }
}
