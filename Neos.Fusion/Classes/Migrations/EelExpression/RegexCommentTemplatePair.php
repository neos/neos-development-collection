<?php

declare(strict_types=1);

namespace Neos\Fusion\Migrations\EelExpression;

use Neos\Flow\Annotations as Flow;

/**
 * @internal
 */
#[Flow\Proxy(false)]
final class RegexCommentTemplatePair
{
    public function __construct(
        public readonly string $regex,
        public readonly string $template,
    ) {
    }
}
