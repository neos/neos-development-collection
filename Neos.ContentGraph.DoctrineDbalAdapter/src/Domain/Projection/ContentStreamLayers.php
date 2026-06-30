<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * Abstraction for content streams
 *
 * Content stream layers allow to share in common hierarchy relations for content streams.
 *
 * Content stream layer are auto incrementing. Only the highest layer is to be written and exclusive for a
 * single content stream. All other layers are shared and must not be mutated.
 * If the highest layer does not contain a hierarchy row to modify we leverage copy on write.
 *
 * Documentation visualized with graphs {@link https://github.com/neos/neos-development-collection/pull/5776}
 *
 * @internal
 */
final readonly class ContentStreamLayers
{
    /**
     * @param array<int,ContentStreamLayer> $items
     */
    private function __construct(
        public array $items
    ) {
    }

    public static function from(ContentStreamLayer ...$items): self
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Content stream layers must not be empty', 1775819046);
        }
        $indexed = array_column($items, null, 'value');
        ksort($indexed, SORT_NUMERIC);
        return new self(
            $indexed,
        );
    }

    /** @param array<int|string,int> $array */
    public static function fromArray(array $array): self
    {
        return self::from(
            ...array_map(ContentStreamLayer::fromInt(...), $array),
        );
    }

    public function getWriteLayer(): ContentStreamLayer
    {
        return $this->items[array_key_last($this->items)];
    }

    public function getRootLayer(): ContentStreamLayer
    {
        return $this->items[array_key_first($this->items)];
    }

    public function getParentReadLayer(): ?ContentStreamLayer
    {
        if (count($this->items) === 1) {
            return null;
        }
        $secondLastItem = array_slice($this->items, -2, 1);
        return $secondLastItem[0];
    }

    public function getParentReadLayers(): ?self
    {
        if (count($this->items) === 1) {
            return null;
        }
        $withoutLastEntry = array_slice($this->items, 0, -1, preserve_keys: true);
        return new self($withoutLastEntry);
    }

    public function equalsSingle(ContentStreamLayer $id): bool
    {
        return count($this->items) === 1 && array_key_exists($id->value, $this->items);
    }

    public function contain(ContentStreamLayer $id): bool
    {
        return array_key_exists($id->value, $this->items);
    }

    /**
     * @return list<int>
     */
    public function toIntArray(): array
    {
        return array_keys($this->items);
    }

    public function toDebugString(): string
    {
        return sprintf('Layers[%s]', join(',', $this->toIntArray()));
    }
}
