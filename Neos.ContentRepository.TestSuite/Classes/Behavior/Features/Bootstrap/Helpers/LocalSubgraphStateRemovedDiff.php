<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\Flow\Annotations as Flow;

/**
 * The difference between two local subgraph states, describing that the latter one is completely removed
 *
 * @internal
 */
#[Flow\Proxy(false)]
final readonly class LocalSubgraphStateRemovedDiff implements \JsonSerializable
{
    public static function create(): self
    {
        return new self();
    }

    public function jsonSerialize(): string
    {
        return '_removed';
    }
}
