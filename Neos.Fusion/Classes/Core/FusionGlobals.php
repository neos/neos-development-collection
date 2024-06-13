<?php

declare(strict_types=1);

namespace Neos\Fusion\Core;

/**
 * Fusion differentiates between dynamic context variables and fixed Fusion globals.
 *
 * Context variables are allowed to be set via Fusion's \@context.foo = "bar"
 * or by leveraging the php api {@see Runtime::pushContext()}.
 *
 * Context variables are highly dynamic and don't guarantee the existence of a specific variables,
 * as they have to be explicitly preserved in uncached \@cache segments,
 * or might accidentally be popped from the stack.
 *
 * The Fusion globals are immutable and part of the runtime's constructor.
 * A fixed set of global variables which might contain the EEL helper definitions
 * or functions like FlowQuery can be passed this way.
 *
 * Additionally, also special variables like "request" are made available.
 *
 * The speciality with "request" and similar is that they should be always available but never cached.
 * Regular context variables must be serialized to be available in uncached segments,
 * but the current request must not be serialized into the cache as it contains user specific information.
 * This is avoided by always exposing the current action request via the global variable.
 *
 * Overriding Fusion globals is disallowed via \@context and {@see Runtime::pushContext()}.
 *
 * Fusion globals are case-sensitive, though it's not recommend to leverage this behaviour.
 *
 * @internal The globals will be set inside the FusionView as declared
 */
final readonly class FusionGlobals
{
    /**
     * @param array<string|mixed> $value
     */
    private function __construct(
        public array $value
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param array<string|mixed> $variables
     */
    public static function fromArray(array $variables): self
    {
        return new self($variables);
    }

    /**
     * Access the possible current request or other globals:
     *
     *     $actionRequest = $this->runtime->fusionGlobals->get('request');
     *     if (!$actionRequest instanceof ActionRequest) {
     *         // fallback or error
     *     }
     *
     */
    public function get(string $name): mixed
    {
        return $this->value[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->value);
    }

    public function merge(FusionGlobals $other): self
    {
        return new self(
            [...$this->value, ...$other->value]
        );
    }
}
