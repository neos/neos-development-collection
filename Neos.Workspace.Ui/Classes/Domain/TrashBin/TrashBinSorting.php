<?php

declare(strict_types=1);

namespace Neos\Workspace\Ui\Domain\TrashBin;

use Neos\Flow\Annotations as Flow;

/**
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
final readonly class TrashBinSorting implements \JsonSerializable
{
    public function __construct(
        public TrashBinSortingPropertyName $propertyName,
        public TrashBinSortingDirection $direction
    ) {
    }

    public function getWithInvertedSorting(): self
    {
        return new self($this->propertyName, $this->direction->invert());
    }

    /**
     * @param array<mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            propertyName: TrashBinSortingPropertyName::from($array['propertyName']),
            direction: TrashBinSortingDirection::from($array['direction']),
        );
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
    }

    public static function default(): self
    {
        return new self(
            propertyName: TrashBinSortingPropertyName::SORTING_PROPERTY_DELETE_TIME,
            direction: TrashBinSortingDirection::SORTING_DESCENDING,
        );
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
