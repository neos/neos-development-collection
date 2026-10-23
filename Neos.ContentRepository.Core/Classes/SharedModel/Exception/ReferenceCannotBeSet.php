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
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
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

    public static function becauseTheReferenceTypeDoesNotExist(
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
        ReferenceTypeName $referenceTypeName,
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for node type "' . $nodeTypeName->value
            . '" because its type "' . $referenceTypeName->value . '" is unknown to the reference type graph.',
            1792139965,
        );
    }

    public static function becauseTheReferenceTypeIsAbstract(
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
        ReferenceTypeName $referenceTypeName,
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for node type "' . $nodeTypeName->value
            . '" because its type "' . $referenceTypeName->value . '" is abstract.',
            1792482976,
        );
    }

    public static function becauseTheReferenceTypeIsDisallowedByTheSourceNodeType(
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
        ReferenceTypeName $referenceTypeName,
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for node type "' . $nodeTypeName->value
            . '" because it does not allow references of type "' . $referenceTypeName->value . '".',
            1792483709,
        );
    }

    public static function becauseTheReferenceTypeDisallowsTheSourceNodeType(
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
        ReferenceTypeName $referenceTypeName,
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for node type "' . $nodeTypeName->value
            . '" because its type "' . $referenceTypeName->value . '" does not allow the node type as source.',
            1792505612,
        );
    }

    public static function becauseTheReferenceTypeDisallowsTheTargetNodeType(
        ReferenceName $referenceName,
        NodeTypeName $targetNodeTypeName,
        ReferenceTypeName $referenceTypeName,
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set with target node of type "' . $targetNodeTypeName->value
            . '" because its type "' . $referenceTypeName->value . '" does not allow the node type as target.',
            1792506353,
        );
    }


    public static function becauseTheReferenceTypeDoesNotDeclareTheProperty(
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
        ReferenceTypeName $referenceTypeName,
        PropertyName $propertyName
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for node type "'
            . $nodeTypeName->value . '" because its type "' . $referenceTypeName->value . '" does not declare given property "' . $propertyName->value . '"',
            1792507086
        );
    }

    public static function becauseAPropertyDoesNotMatchTheTypeDeclaredByTheReferenceType(
        ReferenceName $referenceName,
        ReferenceTypeName $referenceTypeName,
        PropertyName $propertyName,
        string $attemptedType,
        string $configuredType
    ): self {
        return new self(
            'Reference "' . $referenceName->value . '" cannot be set for reference type "' . $referenceTypeName->value
                . '" because its property "' . $propertyName->value . '" cannot be set to a value of type "' . $attemptedType
                . '", must be of type "' . $configuredType . '".',
            1792147895,
        );
    }
}
