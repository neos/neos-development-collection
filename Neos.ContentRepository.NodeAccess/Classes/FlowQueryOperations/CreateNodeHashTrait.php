<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\FlowQueryOperations;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

trait CreateNodeHashTrait
{
    /**
     * Create a string hash containing the node-aggregateId, cr-id, workspace-name, dimensionSpacePoint-hash
     * and visibilityConstraints-hash. To be used for ensuring uniqueness or removing nodes.
     *
     * @see Node::equals() for comparison
     */
    protected function createNodeHash(Node $node): string
    {
        return md5(
            implode(
                ':',
                [
                    $node->nodeAggregateId->value,
                    $node->address->contentRepositoryId->value,
                    $node->address->workspaceName->value,
                    $node->address->dimensionSpacePoint->hash,
                    $node->subgraphIdentity->visibilityConstraints->getHash()
                ]
            )
        );
    }
}
