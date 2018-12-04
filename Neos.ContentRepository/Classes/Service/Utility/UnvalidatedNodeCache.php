<?php
namespace Neos\ContentRepository\Service\Utility;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Simple cache for nodes that is intended to be used in `NodePrivilegeContext`.
 * WARNING: This cache may contain nodes that should not be visible to the current user.
 *          Only use this cache in `NodePrivilegeContext`.
 *
 * @Flow\Scope("singleton")
 */
class UnvalidatedNodeCache
{
    private $nodes = [];

    /**
     * @param string $nodeIdentifier
     * @return bool
     */
    public function has($nodeIdentifier)
    {
        return array_key_exists($nodeIdentifier, $this->nodes);
    }

    /**
     * @param string $nodeIdentifier
     * @return NodeInterface
     */
    public function get($nodeIdentifier)
    {
        return $this->nodes[$nodeIdentifier];
    }

    /**
     * @param string $nodeIdentifier
     * @param NodeInterface $node
     */
    public function put($nodeIdentifier, NodeInterface $node)
    {
        $this->nodes[$nodeIdentifier] = $node;
    }

    /**
     * @param string $nodeIdentifier
     * @param callable $getter
     * @return NodeInterface
     */
    public function cache($nodeIdentifier, callable $getter)
    {
        if ($this->has($nodeIdentifier)) {
            return $this->get($nodeIdentifier);
        }

        $node = $getter();
        if ($node) {
            $this->put($nodeIdentifier, $node);
        }
        return $node;
    }
}
