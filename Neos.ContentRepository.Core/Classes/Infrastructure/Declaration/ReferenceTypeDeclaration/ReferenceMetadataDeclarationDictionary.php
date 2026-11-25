<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration;

use Neos\ContentRepository\Core\ReferenceType\ReferenceMetadataDictionary;
use Neos\ContentRepository\Core\ReferenceType\ReferenceMetadataInterface;

/**
 * @template T of ReferenceMetadataDeclarationInterface
 * @implements \IteratorAggregate<class-string<T>,T>
 * @api
 */
final readonly class ReferenceMetadataDeclarationDictionary implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<class-string<T>,T> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    /**
     * @template I of ReferenceMetadataDeclarationInterface
     * @param I ...$declarations
     * @return self<I>
     */
    public static function create(ReferenceMetadataDeclarationInterface ...$declarations): self
    {
        $items = [];
        foreach ($declarations as $declaration) {
            $items[get_class($declaration)] = $declaration;
        }

        return new self($items);
    }

    /**
     * @param ReferenceMetadataDeclarationDictionary<ReferenceMetadataDeclarationInterface> $subTypePropertyMetadata
     * @return self<ReferenceMetadataDeclarationInterface>
     */
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

    /**
     * @return ReferenceMetadataDictionary<ReferenceMetadataInterface>
     */
    public function toReferenceMetadataDictionary(): ReferenceMetadataDictionary
    {
        return ReferenceMetadataDictionary::create(...array_map(
            fn (ReferenceMetadataDeclarationInterface $item): ReferenceMetadataInterface => $item->toMetadata(),
            $this->items,
        ));
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
