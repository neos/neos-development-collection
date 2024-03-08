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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Traversable;

/**
 * A set of {@see SubtreeTag} instances that are attached to a node.
 *
 * Internally, this consists of two collection:
 * - One for tags that are _explicitly_ set on the respective node.
 * - And one that contains tags that are _inherited_ by one of the ancestor nodes
 *
 * In most cases, it shouldn't matter whether a tag is inherited or set explicitly. But sometimes the behavior is slightly different (e.g. the "disabled" checkbox in the Neos UI inspector is only checked if the node has the `disabled` tag set explicitly)
 *
 * @implements \IteratorAggregate<SubtreeTag>
 * @api
 */
final readonly class NodeSubtreeTags implements \IteratorAggregate, \Countable, \JsonSerializable
{
    private function __construct(
        private SubtreeTags $tags,
        private SubtreeTags $inheritedTags,
    ) {
    }

    public static function create(SubtreeTags $tags, SubtreeTags $inheritedTags): self
    {
        $intersection = $tags->intersection($inheritedTags);
        if (!$intersection->isEmpty()) {
            throw new \InvalidArgumentException(sprintf('tags and inherited tags must not contain the same values, but the following tag%s appear%s in both sets: "%s"', $intersection->count() === 1 ? '' : 's', $intersection->count() === 1 ? 's' : '', implode('", "', $intersection->toStringArray())), 1709891871);
        }
        return new self($tags, $inheritedTags);
    }

    public static function createEmpty(): self
    {
        return new self(SubtreeTags::createEmpty(), SubtreeTags::createEmpty());
    }

    public function without(SubtreeTag $subtreeTagToRemove): self
    {
        if (!$this->tags->contain($subtreeTagToRemove) && !$this->inheritedTags->contain($subtreeTagToRemove)) {
            return $this;
        }
        return new self($this->tags->without($subtreeTagToRemove), $this->inheritedTags->without($subtreeTagToRemove));
    }

    public function withoutInherited(): self
    {
        if ($this->inheritedTags->isEmpty()) {
            return $this;
        }
        return self::create($this->tags, SubtreeTags::createEmpty());
    }

    public function onlyInherited(): self
    {
        if ($this->tags->isEmpty()) {
            return $this;
        }
        return self::create(SubtreeTags::createEmpty(), $this->inheritedTags);
    }

    public function isEmpty(): bool
    {
        return $this->tags->isEmpty() && $this->inheritedTags->isEmpty();
    }

    public function count(): int
    {
        return $this->tags->count() + $this->inheritedTags->count();
    }

    public function contain(SubtreeTag $tag): bool
    {
        return $this->tags->contain($tag) || $this->inheritedTags->contain($tag);
    }

    public function all(): SubtreeTags
    {
        return SubtreeTags::fromArray([...iterator_to_array($this->tags), ...iterator_to_array($this->inheritedTags)]);
    }

    /**
     * @param \Closure(SubtreeTag $tag, bool $inherited): mixed $callback
     * @return array<mixed>
     */
    public function map(\Closure $callback): array
    {
        return [
            ...array_map(static fn (SubtreeTag $tag) => $callback($tag, false), iterator_to_array($this->tags)),
            ...array_map(static fn (SubtreeTag $tag) => $callback($tag, true), iterator_to_array($this->inheritedTags)),
        ];
    }

    /**
     * @return array<string>
     */
    public function toStringArray(): array
    {
        return $this->map(static fn (SubtreeTag $tag) => $tag->value);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->tags as $tag) {
            yield $tag;
        }
        foreach ($this->inheritedTags as $tag) {
            yield $tag;
        }
    }



    /**
     * The JSON representation contains the tag names as keys and a value of `true` for explicitly set tags and `null` for inherited tags.
     * Example: ['someExplicitlySetTag' => true, 'someInheritedTag' => null]
     *
     * @return array<string, null|true>
     */
    public function jsonSerialize(): array
    {
        $convertedSubtreeTags = [];
        foreach ($this->tags as $tag) {
            $convertedSubtreeTags[$tag->value] = true;
        }
        foreach ($this->inheritedTags as $tag) {
            $convertedSubtreeTags[$tag->value] = null;
        }
        return $convertedSubtreeTags;
    }
}
