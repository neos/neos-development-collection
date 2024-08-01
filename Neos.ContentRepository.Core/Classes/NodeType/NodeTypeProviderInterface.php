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

/**
 * @api
 */
interface NodeTypeProviderInterface
{
    public function getNodeTypes(): NodeTypes;
    public function getNodeType(NodeTypeName $nodeTypeName): ?NodeType;

    public function getSubNodeTypeNames(NodeTypeName $nodeTypeName): NodeTypeNames;
}
