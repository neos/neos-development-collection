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
 * @implements \IteratorAggregate<SubtreeTag>
 * @api
 */
final readonly class SubtreeTagsWithInherited implements \IteratorAggregate, \JsonSerializable
{
    private function __construct(
        public SubtreeTags $tags,
        public SubtreeTags $inheritedTags,
    ) {
    }

    public static function create(SubtreeTags $tags, SubtreeTags $inheritedTags): self
    {
        return new self($tags, $inheritedTags);
    }

    public static function createEmpty(): self
    {
        return new self(SubtreeTags::createEmpty(), SubtreeTags::createEmpty());
    }

    public function without(SubtreeTag $subtreeTagToRemove): self
    {
        return new self($this->tags->without($subtreeTagToRemove), $this->inheritedTags->without($subtreeTagToRemove));
    }

    public function contain(SubtreeTag $tag): bool
    {
        return $this->tags->contain($tag) || $this->inheritedTags->contain($tag);
    }

    public function all(): SubtreeTags
    {
        return SubtreeTags::fromArray([...iterator_to_array($this->tags), ...iterator_to_array($this->inheritedTags)]);
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

    public function isEmpty(): bool
    {
        return $this->tags->isEmpty() && $this->inheritedTags->isEmpty();
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
