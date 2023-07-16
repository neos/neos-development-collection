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

/**
 * @api Userland code might have to react to this
 */
final class RootNodeAggregateDoesNotExist extends \RuntimeException
{
    public static function butWasExpectedTo(NodeTypeName $nodeTypeName): self
    {
        return new self(
            'No root node aggregate could be found for node type ' . $nodeTypeName->value,
            1687008819
        );
    }
}
