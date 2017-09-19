<?php

namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Utility;

/**
 * NodeIdentifier
 */
final class NodeIdentifier extends AbstractIdentifier
{
    /**
     * @param NodeName $childNodeName
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeIdentifier
     */
    public static function forAutoCreatedChildNode(NodeName $childNodeName, NodeIdentifier $nodeIdentifier): NodeIdentifier
    {
        return new NodeIdentifier(Utility::buildAutoCreatedChildNodeIdentifier((string)$childNodeName, (string)$nodeIdentifier));
    }
}
