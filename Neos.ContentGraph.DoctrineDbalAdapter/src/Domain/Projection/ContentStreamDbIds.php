<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * @internal
 */
final readonly class ContentStreamDbIds
{
    /**
     * @param array<int,ContentStreamDbId> $items
     */
    private function __construct(
        // todo remove all usages
        // public int $value,
        private ContentStreamDbId $max,
        public array $items
    ) {
    }

    public static function from(ContentStreamDbId ...$items): self
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
            ...array_map(ContentStreamDbId::fromInt(...), $array),
        );
    }

    public function current(): ContentStreamDbId
    {
        return $this->max;
    }

    public function equals(ContentStreamDbId $id): bool
    {
        return count($this->items) === 1 && array_key_exists($id->value, $this->items);
    }

    public function contain(ContentStreamDbId $id): bool
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
