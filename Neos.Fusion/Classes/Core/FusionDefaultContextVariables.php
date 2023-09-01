<?php

declare(strict_types=1);

namespace Neos\Fusion\Core;

use Neos\Utility\Arrays;

/**
 * Variables from configuration that should be set in the context by default.
 * For example Eel helper definitions
 */
final class FusionDefaultContextVariables
{
    protected function __construct(
        /** @var array with default context variable objects. */
        public readonly array $value
    ) {
    }

    public static function empty(): self
    {
        return new static([]);
    }

    public static function fromArray(array $variables): self
    {
        return new static($variables);
    }

    public function merge(FusionDefaultContextVariables $additionalVariables): self
    {
        return new static(
            Arrays::arrayMergeRecursiveOverrule($this->value, $additionalVariables->value)
        );
    }
}
