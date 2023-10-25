<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;

/**
 * The node relation anchor value object
 *
 * @internal
 */
class NodeRelationAnchorPoint implements \JsonSerializable
{
    private function __construct(
        public readonly string $value
    ) {
    }

    public static function create(): self
    {
        return new self(UuidFactory::create());
    }

    public static function forRootEdge(): self
    {
        return new self('00000000-0000-0000-0000-000000000000');
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }
}
