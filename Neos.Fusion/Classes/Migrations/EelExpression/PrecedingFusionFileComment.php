<?php
declare(strict_types=1);

namespace Neos\Fusion\Migrations\EelExpression;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 * @internal
 */
final class PrecedingFusionFileComment
{
    public string $text = '';

    public function __construct(
        public readonly int $lineNumberOfMatch,
        public readonly string $template
    ) {
    }
}
