<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeModification\Dto;

use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * Todo move to shared and use {@see PropertyName} ???
 * @api used as part of commands/events
 */
final readonly class PropertyNames
{
    /**
     * @var array<string>
     */
    public array $values;

    private function __construct(
        string ...$propertyNames
    ) {
        $this->values = $propertyNames;
    }

    /**
     * @param array<string> $propertyNames
     */
    public static function fromArray(array $propertyNames): self
    {
        return new self(...$propertyNames);
    }

    public static function createEmpty(): self
    {
        return new self();
    }
}
