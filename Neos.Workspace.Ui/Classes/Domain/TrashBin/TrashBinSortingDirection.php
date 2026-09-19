<?php

declare(strict_types=1);

namespace Neos\Workspace\Ui\Domain\TrashBin;

/**
 * @internal for communication within the Workspace UI only
 */
enum TrashBinSortingDirection: string implements \JsonSerializable
{
    case SORTING_DESCENDING = 'desc';

    case SORTING_ASCENDING = 'asc';

    public function invert(): self
    {
        return match ($this) {
            self::SORTING_DESCENDING => self::SORTING_ASCENDING,
            self::SORTING_ASCENDING => self::SORTING_DESCENDING,
        };
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
