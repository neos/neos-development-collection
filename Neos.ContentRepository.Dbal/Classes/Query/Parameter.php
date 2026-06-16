<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;

/**
 * @internal
 */
final readonly class Parameter
{
    private function __construct(
        public string $name,
        public int|string|Type|null $type,
        public mixed $value,
    ) {
    }

    public static function string(string $name, string $value): self
    {
        return new self(
            name: $name,
            type: ParameterType::STRING,
            value: $value
        );
    }

    /**
     * @param list<string> $value
     */
    public static function stringArray(string $name, array $value): self
    {
        return new self(
            name: $name,
            type: ArrayParameterType::STRING,
            value: $value
        );
    }

    public static function integer(string $name, int $value): self
    {
        return new self(
            name: $name,
            type: ParameterType::INTEGER,
            value: $value
        );
    }

    /**
     * @param list<int> $value
     */
    public static function integerArray(string $name, array $value): self
    {
        return new self(
            name: $name,
            type: ArrayParameterType::INTEGER,
            value: $value
        );
    }
}
