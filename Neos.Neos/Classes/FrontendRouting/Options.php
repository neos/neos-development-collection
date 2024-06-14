<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting;

/**
 * Immutable filter DTO for {@see NodeUriBuilder::uriFor()}
 *
 * Example:
 *
 *   Options::create(forceAbsolute: true);
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
        public bool $forceAbsolute,
        public string $format,
        public array $routingArguments,
    ) {
    }

    /**
     * Creates an instance with the specified options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public static function create(
        bool $forceAbsolute = null
    ): self {
        return new self(
            $forceAbsolute ?? false,
            '',
            []
        );
    }

    /**
     * Returns a new instance with the specified additional options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public function with(
        bool $forceAbsolute = null
    ): self {
        return new self(
            $forceAbsolute ?? $this->forceAbsolute,
            $this->format,
            $this->routingArguments
        );
    }

    /**
     * Option to set a custom routing format
     *
     * In order for the routing framework to match and resolve this format,
     * your have to define a custom route in Routes.yaml
     *
     *   -
     *     name: 'Neos :: Frontend :: Document node with json format'
     *     uriPattern: '{node}.json'
     *     defaults:
     *       '@package': Neos.Neos
     *       '@controller': Frontend\Node
     *       '@action': show
     *       '@format': json
     *     routeParts:
     *       node:
     *         handler: Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface
     *
     * See also {@link https://docs.neos.io/guide/rendering/rendering-special-formats}
     */
    public function withCustomFormat(string $format): self
    {
        return new self($this->forceAbsolute, $format, $this->routingArguments);
    }

    /**
     * Option to set custom routing arguments
     *
     * Please do not use this functionality to append query parameters
     * and use {@see \Neos\Flow\Http\UriHelper::withAdditionalQueryParameters} instead:
     *
     *   UriHelper::withAdditionalQueryParameters(
     *     $this->nodeUriBuilder->uriFor(...),
     *     ['q' => 'search term']
     *   );
     *
     * Appending query parameters via the use of exceeding routing arguments relies
     * on `appendExceedingArguments` internally which is discouraged to leverage.
     *
     * But in case you meant to use routing arguments for advanced uri building,
     * you can leverage this low level option.
     *
     * Be aware in order for the routing framework to match and resolve the arguments,
     * your have to define a custom route in Routes.yaml
     *
     *   -
     *     name: 'Neos :: Frontend :: Document node with pagination'
     *     uriPattern: '{node}/page-{page}'
     *     defaults:
     *       '@package': Neos.Neos
     *       '@controller': Frontend\Node
     *       '@action': show
     *     routeParts:
     *       node:
     *         handler: Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface
     *
     * @param array<string, mixed> $routingArguments
     */
    public function withCustomRoutingArguments(array $routingArguments): self
    {
        return new self($this->forceAbsolute, $this->format, $routingArguments);
    }
}
