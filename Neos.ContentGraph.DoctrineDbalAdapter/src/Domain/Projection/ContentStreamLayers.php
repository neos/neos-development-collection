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
        // todo remove all usages
        // public int $value,
        private ContentStreamLayer $max,
        public array $items
    ) {
    }

    public static function from(ContentStreamLayer ...$items): self
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Db ids must not be empty', 1775819046);
        }
        $max = [];
        $indexed = [];
        foreach ($items as $id) {
            $indexed[$id->value] = $id;
            $max[] = $id->value;
        }
        return new self(
            max: $indexed[max($max)],
            items: $indexed,
        );
    }

    /** @param array<int|string,int> $array */
    public static function fromArray(array $array): self
    {
        return self::from(
            ...array_map(ContentStreamLayer::fromInt(...), $array),
        );
    }

    public function current(): ContentStreamLayer
    {
        return $this->max;
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
