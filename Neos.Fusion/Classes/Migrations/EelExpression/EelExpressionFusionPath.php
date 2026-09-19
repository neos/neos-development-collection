<?php

namespace Neos\Fusion\Migrations\EelExpression;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 * @internal
 */
final class EelExpressionFusionPath
{
    private function __construct(
        private readonly array $segments
    ) {
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public static function create(?array $currentPath): self
    {
        if (!$currentPath) {
            return self::createEmpty();
        }
        return new self($currentPath);
    }

    public function contains(string $otherFusionPath): bool
    {
        // for comparison and exposed api we use the neos runtime path formatting - more or less - which uses slashes. (Fun fact the fusion runtime cannot handle slashes in quoted paths so this is fine :D)
        return str_contains(
            join('/', $this->segments),
            '/' . trim($otherFusionPath, '/') . '/'
        );
    }
}
