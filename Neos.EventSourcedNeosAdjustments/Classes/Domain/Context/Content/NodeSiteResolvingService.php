<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Domain\Context\Content;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NodeSiteResolvingService
{

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    public function findSiteNodeForNodeAddress(NodeAddress $nodeAddress): ?NodeInterface
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $nodeAddress->getContentStreamIdentifier(),
            $nodeAddress->getDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->getNodeAggregateIdentifier());
        $previousNode = null;
        do {
            if ($node->getNodeType()->isOfType('Neos.Neos:Sites')) {
                // the Site node is the one one level underneath the "Sites" node.
                return $previousNode;
            }
            $previousNode = $node;
        } while ($node = $subgraph->findParentNode($node->getNodeAggregateIdentifier()));

        // no Site node found at rootline
        return null;
    }
}
