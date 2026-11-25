<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\ReferenceLabel;

use Neos\ContentRepository\Core\Infrastructure\Declaration\Undeclared;

trait GenericReferenceTypeMetadata
{
    /**
     * @param static $subTypeMetadata
     */
    public function mergeToSubType($subTypeMetadata): static
    {
        return new static(...array_map(
            fn (mixed $value, string $propertyName): mixed => $this->{$propertyName} instanceof Undeclared
                ? $subTypeMetadata->{$propertyName}
                : $this->{$propertyName},
            get_object_vars($this),
            array_keys(get_object_vars($this)),
        ));
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            get_object_vars($this),
            fn (mixed $member): bool => !$member instanceof Undeclared,
        );
    }
}
