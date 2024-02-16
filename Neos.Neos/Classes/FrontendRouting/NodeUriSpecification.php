<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting;

use Neos\ContentRepository\Core\SharedModel\Node\NodeIdentity;

final readonly class NodeUriSpecification
{
    /**
     * @param array<string, mixed> $routingArguments
     */
    private function __construct(
        public NodeIdentity $node,
        public string $format,
        public array $routingArguments,
    ) {
    }

    public static function create(NodeIdentity $node): self
    {
        return new self($node, '', []);
    }

    public function withFormat(string $format): self
    {
        return new self($this->node, $format, $this->routingArguments);
    }

    /**
     * @deprecated if you meant to append query parameters,
     * please use {@see \Neos\Flow\Http\UriHelper::withAdditionalQueryParameters} instead:
     *
     * ```php
     * UriHelper::withAdditionalQueryParameters($this->nodeUriBuilder->uriFor(...), ['q' => 'search term']);
     * ```
     *
     * @param array<string, mixed> $routingArguments
     */
    public function withRoutingArguments(array $routingArguments): self
    {
        return new self($this->node, $this->format, $routingArguments);
    }
}
