<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Exception;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * The exception to be thrown if a reference was attempted to be set but cannot be
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class ReferenceCannotBeSet extends \DomainException
{
    public static function becauseTheNodeTypeDoesNotDeclareIt(
        ReferenceName $propertyName,
        NodeTypeName $nodeTypeName
    ): self {
        return new self(
            'Reference "' . $propertyName->value . '" cannot be set because node type "'
                . $nodeTypeName->value . '" does not declare it.',
            1618670106
        );
    }

    public static function becauseTheNodeTypeConstraintsAreNotMatched(
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
        NodeTypeName $nameOfAttemptedType
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for node type "'
            . $nodeTypeName->value . '" because the constraints do not allow nodes of type "' . $nameOfAttemptedType->value . '"',
            1648502149
        );
    }

    public static function becauseTheItemsCountConstraintsAreNotMatched(
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
        int $numberOfAttemptedReferencesToWrite
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for node type "'
            . $nodeTypeName->value . '" because the constraints do not allow to set ' . $numberOfAttemptedReferencesToWrite . ' references',
            1700150156
        );
    }

    public static function becauseTheReferenceDoesNotDeclareTheProperty(
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
        PropertyName $propertyName
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for node type "'
            . $nodeTypeName->value . '" because it does not declare given property "' . $propertyName->value . '"',
            1658406662
        );
    }

    public static function becauseAPropertyDoesNotMatchTheDeclaredType(
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
        PropertyName $propertyName,
        string $attemptedType,
        string $configuredType
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for node type "' . $nodeTypeName->value
            . '" because its property "' . $propertyName->value . '" cannot be set to a value of type "' . $attemptedType
            . '", must be of type "' . $configuredType . '".',
            1658406762
        );
    }
}
