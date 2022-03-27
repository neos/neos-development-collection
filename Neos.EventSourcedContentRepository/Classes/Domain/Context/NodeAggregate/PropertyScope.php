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

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

use Neos\Flow\Annotations as Flow;

/**
 * The property scope to be used in NodeType property declarations.
 * Will affect node operations on properties in that they decide which of the node's variants will be modified as well.
 */
#[Flow\Proxy(false)]
enum PropertyScope: string implements \JsonSerializable
{
    /**
     * The "node" scope, meaning only the node in the selected origin will be modified
     */
    case SCOPE_NODE = 'node';

    /**
     * The "specializations" scope, meaning only the node and its specializations will be modified
     */
    case SCOPE_SPECIALIZATIONS = 'specializations';

    /**
     * The "nodeAggregate" scope, meaning that all variants, e.g. all nodes in the aggregate will be modified
     */
    case SCOPE_NODE_AGGREGATE = 'nodeAggregate';

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
