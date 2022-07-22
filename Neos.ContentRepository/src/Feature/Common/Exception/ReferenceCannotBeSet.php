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
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a reference was attempted to be set but cannot be
 */
#[Flow\Proxy(false)]
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
}
