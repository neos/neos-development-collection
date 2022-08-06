<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;

/**
 * The node relation anchor value object
 */
#[Flow\Proxy(false)]
final class NodeRelationAnchorPoint implements \JsonSerializable, \Stringable
{
    private function __construct(
        public readonly string $value
    ) {
    }

    public static function create(): self
    {
        return new self(Algorithms::generateUUID());
    }

    public static function forRootHierarchyRelation(): self
    {
        return new self('00000000-0000-0000-0000-000000000000');
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
