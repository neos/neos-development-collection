<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Helper;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\ProtectedContextAwareInterface;

/**
 * Helper to build label in NodeType definition
 *
 * Note, this helper is aliased Neos.Node but not to be confused with {@see NodeHelper} and only api for EEL
 */
class NodeLabelHelper implements ProtectedContextAwareInterface
{
    /**
     * Generate a label for a node with a chaining mechanism. To be used in NodeType definition:
     *
     *     'Vendor.Site:MyContent':
     *       label: "${Neos.Node.labelForNode(node).prefix('foo')}"
     *
     */
    public function labelForNode(Node $node): NodeLabelToken
    {
        return new NodeLabelToken($node);
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
