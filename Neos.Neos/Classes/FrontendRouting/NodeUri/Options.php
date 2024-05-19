<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\NodeUri;

/*
 * Immutable filter DTO for {@see NodeUriBuilder::uriFor()}
 *
 * Example:
 *
 * Options::create(forceAbsolute: true);
 *
 * @api for the factory methods; NOT for the inner state.
 */
final readonly class Options
{
    /**
     * @internal the properties themselves are readonly; only the write-methods are API.
     * @param array<string, mixed> $routingArguments
     */
    private function __construct(
        public ?bool $forceAbsolute,
        public ?string $format,
        public ?array $routingArguments,
    ) {
    }

    /**
     * Creates an instance with the specified options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     *
     * @param array<string, mixed> $routingArguments
     */
    public static function create(
        bool $forceAbsolute = null,
        string $format = null,
        array $routingArguments = null,
    ): self {
        return new self($forceAbsolute, $format, $routingArguments);
    }

    /**
     * Returns a new instance with the specified additional options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     *
     * @param array<string, mixed> $routingArguments
     */
    public function with(
        bool $forceAbsolute = null,
        string $format = null,
        array $routingArguments = null,
    ): self {
        return self::create(
            $forceAbsolute ?? $this->forceAbsolute,
            $format ?? $this->format,
            $routingArguments ?? $this->routingArguments,
        );
    }
}
