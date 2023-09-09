<?php

declare(strict_types=1);

namespace Neos\Fusion\Core;

use Neos\Utility\Arrays;

/**
 * Fusion allows to add variable to the context either via
 * \@context.foo = "bar" or by leveraging the php api {@see Runtime::pushContext()}.
 *
 * Those approaches are highly dynamic and don't guarantee the existence of variables,
 * as they have to be explicitly preserved in uncached \@cache segments,
 * or might accidentally be popped from the stack.
 *
 * The Fusion runtime is instantiated with a set of global variables which contain the EEL helper definitions
 * or functions like FlowQuery. Also, variables like "request" are made available via it.
 *
 * The "${request}" special case: To make the request available in uncached segments, it would need to be serialized,
 * but we don't allow this currently and despite that, it would be absurd to cache a random request.
 * This is avoided by always exposing the current action request via the global variable.
 *
 * Overriding Fusion globals is disallowed via \@context and {@see Runtime::pushContext()}.
 */
final readonly class FusionGlobals
{
    private function __construct(
        public array $value
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromArray(array $variables): self
    {
        return new self($variables);
    }

    /**
     * You can access the current request like via this getter:
     * `$runtime->fusionGlobals->getGlobal('request')`
     */
    public function getGlobal(string $name): mixed
    {
        return $this->value[$name] ?? null;
    }

    public function merge(FusionGlobals $other): self
    {
        return new self(
            Arrays::arrayMergeRecursiveOverrule($this->value, $other->value)
        );
    }
}
