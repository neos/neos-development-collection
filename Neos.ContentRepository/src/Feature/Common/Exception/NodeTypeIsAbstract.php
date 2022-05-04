<?php

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
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a node type is abstract but was not supposed to be
 */
#[Flow\Proxy(false)]
final class NodeTypeIsAbstract extends \DomainException
{
    public static function butWasNotSupposedToBe(NodeTypeName $nodeTypeName): self
    {
        return new self('Given node type "' . $nodeTypeName . '" is abstract.', 1630061720);
    }
}
