<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Exception;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * The exception to be thrown if a property type is invalid
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class PropertyTypeIsInvalid extends \DomainException
{
    public static function becauseItIsUndefined(
        PropertyName $propertyName,
        string $declaredType,
        NodeTypeName $nodeTypeName
    ): self {
        return new self(
            'Given property "' . $propertyName->value . '" is declared as undefined type "' . $declaredType
                . '" in node type "' . $nodeTypeName->value . '"',
            1630063406
        );
    }
}
