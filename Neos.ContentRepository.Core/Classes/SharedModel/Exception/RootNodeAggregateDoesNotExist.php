<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Exception;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 * @api Userland code might have to react to this
 */
final class RootNodeAggregateDoesNotExist extends \DomainException
{
    public static function butWasExpectedTo(NodeTypeName $nodeTypeName): self
    {
        return new self(
            'No root node aggregate could be found for node type ' . $nodeTypeName->value,
            1687008819
        );
    }
}
