<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\NodeType\NodeType;

/**
 * @api
 */
final readonly class PropertyNames implements \JsonSerializable
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

    /**
     * @return array<string, self>
     */
    public function splitByScope(NodeType $nodeType): array
    {
        $propertiesToUnsetByScope = [];
        foreach ($this->values as $propertyName) {
            $scope = PropertyScope::tryFromDeclaration($nodeType, PropertyName::fromString($propertyName));
            $propertiesToUnsetByScope[$scope->value][] = $propertyName;
        }

        return array_map(
            fn(array $propertyValues): self => self::fromArray($propertyValues),
            $propertiesToUnsetByScope
        );
    }

    public function merge(self $other): self
    {
        return new self(...$this->values, ...$other->values);
    }

    public function jsonSerialize(): mixed
    {
        return $this->values;
    }
}
