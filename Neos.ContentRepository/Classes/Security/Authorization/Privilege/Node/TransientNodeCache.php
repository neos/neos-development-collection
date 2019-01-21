<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Security\Authorization\Privilege\Node;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Simple cache for nodes that is intended to be used in `NodePrivilegeContext`.
 *
 * WARNING: This cache may contain nodes that should not be visible to the current user.
 *          Only use this cache in `NodePrivilegeContext`.
 *
 * @Flow\Scope("singleton")
 */
class TransientNodeCache
{
    private $nodes = [];

    /**
     * @param string $nodeIdentifier
     * @return bool
     */
    private function has(string $nodeIdentifier): bool
    {
        return array_key_exists($nodeIdentifier, $this->nodes);
    }

    /**
     * @param string $nodeIdentifier
     * @return NodeInterface
     */
    private function get(string $nodeIdentifier): NodeInterface
    {
        return $this->nodes[$nodeIdentifier];
    }

    /**
     * @param string $nodeIdentifier
     * @param NodeInterface $node
     */
    private function put(string $nodeIdentifier, NodeInterface $node)
    {
        $this->nodes[$nodeIdentifier] = $node;
    }

    /**
     * @param string $nodeIdentifier
     * @param callable $getter
     * @return NodeInterface
     */
    public function cache(string $nodeIdentifier, callable $getter)
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
