<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\FlowQueryOperations;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

trait CreateNodeHashTrait
{
    /**
     * Create a string hash containing the nodeAggregateId, cr-id, contentStream->id, dimensionSpacePoint->hash
     * and visibilityConstraints->hash. To be used for ensuring uniqueness or removing nodes.
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
                    $node->subgraphIdentity->contentRepositoryId->value,
                    $node->subgraphIdentity->contentStreamId->value,
                    $node->subgraphIdentity->dimensionSpacePoint->hash,
                    $node->subgraphIdentity->visibilityConstraints->getHash()
                ]
            )
        );
    }
}
