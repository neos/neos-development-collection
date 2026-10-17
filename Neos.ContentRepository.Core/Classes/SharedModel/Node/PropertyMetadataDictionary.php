<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

/**
 * @implements \IteratorAggregate<string,PropertyMetadataInterface>
 * @api
 */
final readonly class PropertyMetadataDictionary implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<string,PropertyMetadataInterface> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    /**
     * @param array<string,PropertyMetadataInterface> $items
     */
    public static function create(array $items): self
    {
        return new self($items);
    }

    public function mergeToSubType(self $subTypePropertyMetadata): self
    {
        $items = $this->items;
        foreach ($subTypePropertyMetadata->items as $key => $item) {
            if (array_key_exists($key, $items)) {
                $items[$key] = $items[$key]->mergeToSubType($item);
            } else {
                $items[$key] = $item;
            }
        }

        return new self($items);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
