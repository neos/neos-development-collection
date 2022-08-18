<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
class NodeSiteResolvingService
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    public function findSiteNodeForNodeAddress(
        NodeAddress $nodeAddress,
        ContentRepositoryIdentifier $contentRepositoryIdentifier
    ): ?Node {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $contentRepositoryIdentifier
        );
        $subgraph = $contentRepository->getContentGraph()->getSubgraph(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            $nodeAddress->isInLiveWorkspace()
                ? VisibilityConstraints::frontend()
                : VisibilityConstraints::withoutRestrictions()
        );
        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->nodeAggregateIdentifier);
        if (is_null($node)) {
            return null;
        }
        $previousNode = null;
        do {
            if ($node->nodeType->isOfType('Neos.Neos:Sites')) {
                // the Site node is the one one level underneath the "Sites" node.
                return $previousNode;
            }
            $previousNode = $node;
        } while ($node = $subgraph->findParentNode($node->nodeAggregateIdentifier));

        // no Site node found at rootline
        return null;
    }
}
