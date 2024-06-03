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

namespace Neos\ContentRepository\Core\NodeType;

use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * @api
 */
final readonly class TetheredNodeTypeDefinition
{
    public function __construct(
        public NodeName $name,
        public NodeTypeName $nodeTypeName
    ) {
    }
}
