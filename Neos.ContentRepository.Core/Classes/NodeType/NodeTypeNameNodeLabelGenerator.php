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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * The node type name based node label generator
 *
 * @internal
 */
final class NodeTypeNameNodeLabelGenerator implements NodeLabelGeneratorInterface
{
    public function getLabel(Node $node): string
    {
        return \mb_substr($node->nodeTypeName->value, \mb_strrpos($node->nodeTypeName->value, '.') + 1);
    }
}
