<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;

/**
 * @api
 */
final readonly class PropertyDeclaration implements \JsonSerializable
{
    private function __construct(
        public PropertyName $name,
        public PropertyType $type,
        public PropertyMetadataDictionary $metadata,
    ) {
    }

    public static function create(
        PropertyName $name,
        PropertyType $type,
        PropertyMetadataDictionary $metadata,
    ): self {
        return new self(
            name: $name,
            type: $type,
            metadata: $metadata,
        );
    }

    public function mergeToSubType(self $subTypePropertySpecification): self
    {
        return new self(
            $this->name,
            $this->type,
            $this->metadata->mergeToSubType($subTypePropertySpecification->metadata),
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
