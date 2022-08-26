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

namespace Neos\ContentRepository\Feature\Common\Exception;

use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

/**
 * The exception to be thrown if a reference was attempted to be set but cannot be
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class ReferenceCannotBeSet extends \DomainException
{
    public static function becauseTheNodeTypeDoesNotDeclareIt(
        PropertyName $propertyName,
        NodeTypeName $nodeTypeName
    ): self {
        return new self(
            'Reference "' . $propertyName . '" cannot be set because node type "'
                . $nodeTypeName . '" does not declare it.',
            1618670106
        );
    }

    public static function becauseTheConstraintsAreNotMatched(
        PropertyName $referenceName,
        NodeTypeName $nodeTypeName,
        NodeTypeName $nameOfAttemptedType
    ): self {
        return new self(
            'Reference "' . $referenceName . '" cannot be set for node type "'
            . $nodeTypeName . '" because the constraints do not allow nodes of type "' . $nameOfAttemptedType . '"',
            1648502149
        );
    }

    public static function becauseTheItDoesNotDeclareAProperty(
        PropertyName $referenceName,
        NodeTypeName $nodeTypeName,
        PropertyName $propertyName
    ): self {
        return new self(
            'Reference "' . $referenceName . '" cannot be set for node type "'
            . $nodeTypeName . '" because it does not declare given property "' . $propertyName . '"',
            1658406662
        );
    }

    public static function becauseAPropertyDoesNotMatchTheDeclaredType(
        PropertyName $referenceName,
        NodeTypeName $nodeTypeName,
        PropertyName $propertyName,
        string $attemptedType,
        string $configuredType
    ): self {
        return new self(
            'Reference "' . $referenceName . '" cannot be set for node type "' . $nodeTypeName
            . '" because its property "' . $propertyName . '" cannot be set to a value of type "' . $attemptedType
            . '", must be of type "' . $configuredType . '".',
            1658406762
        );
    }
}
