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

    /**
     * @var NodeInterface[]
     */
    private $nodes = [];

    /**
     * @param string $nodeIdentifier
     * @param callable $getter
     * @return NodeInterface
     */
    public function cache(string $nodeIdentifier, callable $getter): ?NodeInterface
    {
        if (array_key_exists($nodeIdentifier, $this->nodes)) {
            return $this->nodes[$nodeIdentifier];
        }

        $node = $getter();
        if ($node instanceof NodeInterface) {
            $this->nodes[$nodeIdentifier] = $node;
        }
        return $node;
    }
}
