<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Common\Exception;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

/**
 * The exception to be thrown if a property type is invalid
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class PropertyTypeIsInvalid extends \DomainException
{
    public static function becauseItIsReference(PropertyName $propertyName, NodeTypeName $nodeTypeName): self
    {
        return new self(
            'Given property "' . $propertyName . '" is declared as "reference" in node type "'
                . $nodeTypeName . '" and must be treated as such.',
            1630063201
        );
    }

    public static function becauseItIsUndefined(
        PropertyName $propertyName,
        string $declaredType,
        NodeTypeName $nodeTypeName
    ): self {
        return new self(
            'Given property "' . $propertyName . '" is declared as undefined type "' . $declaredType
                . '" in node type "' . $nodeTypeName . '"',
            1630063406
        );
    }
}
