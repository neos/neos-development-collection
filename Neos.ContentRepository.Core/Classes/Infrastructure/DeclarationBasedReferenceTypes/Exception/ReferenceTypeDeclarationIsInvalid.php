<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;

/**
 * The exception to be thrown if an invalid reference type declaration was attempted to be initialized
 * @internal
 */
final class ReferenceTypeDeclarationIsInvalid extends \Exception
{
    public static function becauseItIsBothAbstractAndFinal(ReferenceTypeName $referenceTypeName): self
    {
        return new self(
            'Reference type "' . $referenceTypeName->value
            . '" is both abstract and final but can only be one.',
            1792178940,
        );
    }

    public static function becauseItHasUnknownSuperTypes(
        ReferenceTypeName $referenceTypeName,
        ReferenceTypeNameList $unknownSuperTypes,
    ): self {
        return new self(
            'Reference type "' . $referenceTypeName->value
            . '" has unknown supertypes: "' . implode(", ", $unknownSuperTypes->toStringArray()) . '".',
            1792186715,
        );
    }

    public static function becauseItHasFinalSuperTypes(
        ReferenceTypeName $referenceTypeName,
        ReferenceTypeNameList $finalSuperTypes,
    ): self {
        return new self(
            'Reference type "' . $referenceTypeName->value
            . '" has final supertypes: "' . implode(", ", $finalSuperTypes->toStringArray()) . '".',
            1792183539,
        );
    }

    public static function becauseItHasCircularSuperTypes(
        ReferenceTypeName $referenceTypeName,
        ReferenceTypeNameList $circularSuperTypes,
    ): self {
        return new self(
            'Reference type "' . $referenceTypeName->value
            . '" has circular supertypes: "' . implode(" -> ", $circularSuperTypes->toStringArray()) . '".',
            1792227116,
        );
    }

    public static function becauseItHasConflictingSuperTypes(
        ReferenceTypeName $referenceTypeName,
        SuperTypeConflicts $superTypeConflicts,
    ): self {
        return new self(
            'Reference type "' . $referenceTypeName->value
            . '" has conflicting supertypes: ' . $superTypeConflicts->toDebugString() . '.',
            1792270310,
        );
    }
}
