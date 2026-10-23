<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Property;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * @api
 */
final readonly class Property implements \JsonSerializable
{
    private function __construct(
        public PropertyName $name,
        public PropertyType $type,
        public mixed $defaultValue,
        public PropertyMetadataDictionary $metadata,
    ) {
    }

    public static function create(
        PropertyName $name,
        PropertyType $type,
        mixed $defaultValue,
        PropertyMetadataDictionary $metadata,
    ): self {
        return new self(
            name: $name,
            type: $type,
            defaultValue: $defaultValue,
            metadata: $metadata,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
