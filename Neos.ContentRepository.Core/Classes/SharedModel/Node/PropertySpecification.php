<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;

/**
 * @api
 */
final readonly class PropertySpecification implements \JsonSerializable
{
    private function __construct(
        public PropertyName $name,
        public PropertyType $type,
    ) {
    }

    public static function create(
        PropertyName $name,
        PropertyType $type,
    ): self {
        return new self(
            name: $name,
            type: $type,
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
