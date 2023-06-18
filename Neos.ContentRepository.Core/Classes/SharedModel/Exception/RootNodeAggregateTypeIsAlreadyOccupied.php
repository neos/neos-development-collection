<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Exception;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 * @api Userland code might have to react to this
 */
final class RootNodeAggregateTypeIsAlreadyOccupied extends \DomainException
{
    public static function butWasExpectedNotTo(NodeTypeName $nodeTypeName): self
    {
        return new self(
            'Root node type  ' . $nodeTypeName->value . ' is already occupied',
            1687009058
        );
    }
}
