<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeAttributes\Dto;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * @api
 * @implements \IteratorAggregate<Attribute>
 */
final readonly class Attributes implements \IteratorAggregate, \JsonSerializable
{

    /**
     * @var array<Attribute>
     */
    private array $attributes;

    private function __construct(Attribute ...$attributes)
    {
        $this->attributes = $attributes;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public static function fromStringArray(array $array): self
    {
        return new self(...array_map(Attribute::fromString(...), $array));
    }

    public function isEmpty(): bool
    {
        return $this->attributes === [];
    }

    /**
     * @return array<string>
     */
    public function toStringArray(): array
    {
        return array_map(static fn (Attribute $attribute) => $attribute->value, $this->attributes);
    }

    public function jsonSerialize(): array
    {
        return $this->attributes;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->attributes);
    }
}
