<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;

/**
 * A type-safe collection of {@see SubtreeTag} instances
 *
 * @api
 * @implements \IteratorAggregate<SubtreeTag>
 */
final readonly class SubtreeTags implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array<SubtreeTag>
     */
    private array $tags;


    private function __construct(SubtreeTag ...$tags)
    {
        $this->tags = $tags;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public static function fromStringArray(array $array): self
    {
        return new self(...array_map(SubtreeTag::fromString(...), $array));
    }

    public function isEmpty(): bool
    {
        return $this->tags === [];
    }

    /**
     * @return array<string>
     */
    public function toStringArray(): array
    {
        return array_map(static fn (SubtreeTag $tag) => $tag->value, $this->tags);
    }

    public function jsonSerialize(): array
    {
        return $this->tags;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->tags);
    }
}
