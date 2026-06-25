<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Query;

use Doctrine\DBAL\Types\Type;

/**
 * @internal
 */
class AmbiguousParametersGiven extends \RuntimeException
{
    public static function becauseParameterIsAlreadyDefinedWithValue(string $parameterName, mixed $exitingValue, mixed $attemptedValue): self
    {
        return new self(
            sprintf('Parameter "%s" was already defined with value %s, cannot overrule with different value %s', $parameterName, json_encode($exitingValue), json_encode($attemptedValue)),
            1781604684
        );
    }

    public static function becauseParameterIsAlreadyDefinedWithType(string $parameterName, int|string|Type|null $exitingType, int|string|Type|null $attemptedType): self
    {
        return new self(
            sprintf('Parameter "%s" was already defined with value %s, cannot overrule with different value %s', $parameterName, self::printType($exitingType), self::printType($attemptedType)),
            1781604684
        );
    }

    private static function printType(int|string|Type|null $type): string
    {
        if ($type instanceof Type) {
            return $type->getName();
        }

        if ($type === null) {
            return '[null]';
        }

        return (string) $type;
    }
}
