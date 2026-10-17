<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\Utility\Arrays;

/**
 * @api
 */
final readonly class GenericArrayPropertyMetadata implements PropertyMetadataInterface
{
    /**
     * @param array<string,mixed> $values
     */
    private function __construct(
        public array $values,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function create(array $values): self
    {
        return new self($values);
    }

    /**
     * @param self $subTypeMetadata
     */
    public function mergeToSubType($subTypeMetadata): static
    {
        return new self(
            Arrays::arrayMergeRecursiveOverrule(
                $this->values,
                $subTypeMetadata->values,
            )
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }
}
