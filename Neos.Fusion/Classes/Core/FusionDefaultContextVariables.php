<?php

declare(strict_types=1);

namespace Neos\Fusion\Core;

use Neos\Flow\Mvc\ActionRequest;

/**
 * Variables from configuration that should be set in the context by default.
 * For example Eel helper definitions
 */
final class FusionDefaultContextVariables
{
    protected function __construct(
        public readonly ActionRequest $actionRequest,
        /** @var array with default context variable objects. */
        public readonly array $value
    ) {
    }

    public static function fromRequestAndVariables(ActionRequest $actionRequest, array $additionalVariables = []): self
    {
        return new static(
            $actionRequest,
            ['request' => $actionRequest, ...$additionalVariables]
        );
    }

    public function merge(array $additionalVariables): self
    {
        return new static(
            $this->actionRequest,
            [...$this->value, ...$additionalVariables]
        );
    }
}
