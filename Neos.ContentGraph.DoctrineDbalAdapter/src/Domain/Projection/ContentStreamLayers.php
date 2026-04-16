<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
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
            throw new \InvalidArgumentException('Db ids must not be empty', 1775819046);
        }
        $indexed = [];
        foreach ($items as $id) {
            $indexed[$id->value] = $id;
        }
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
        $items = $this->items;
        unset($items[array_key_last($items)]);
        $secondLast = array_key_last($items);
        if ($secondLast === null) {
            return null;
        }
        return $items[$secondLast];
    }

    public function getParentReadLayers(): ?self
    {
        $items = $this->items;
        unset($items[array_key_last($items)]);
        if ($items === []) {
            return null;
        }
        return new self($items);
    }

    public function equals(ContentStreamLayer $id): bool
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
        return sprintf('DbIds[%s]', join(',', $this->toIntArray()));
    }
}
