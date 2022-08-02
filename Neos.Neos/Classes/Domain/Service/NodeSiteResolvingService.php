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

use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
class NodeSiteResolvingService
{
    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    public function findSiteNodeForNodeAddress(NodeAddress $nodeAddress, ContentRepositoryIdentifier $contentRepositoryIdentifier): ?NodeInterface
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            new ContentSubgraphIdentity(
                $contentRepositoryIdentifier,
                $nodeAddress->contentStreamIdentifier,
                $nodeAddress->dimensionSpacePoint,
                $nodeAddress->isInLiveWorkspace()
                    ? VisibilityConstraints::frontend()
                    : VisibilityConstraints::withoutRestrictions()
            )
        );
        $node = $nodeAccessor->findByIdentifier($nodeAddress->nodeAggregateIdentifier);
        if (is_null($node)) {
            return null;
        }
        $previousNode = null;
        do {
            if ($node->getNodeType()->isOfType('Neos.Neos:Sites')) {
                // the Site node is the one one level underneath the "Sites" node.
                return $previousNode;
            }
            $previousNode = $node;
        } while ($node = $nodeAccessor->findParentNode($node));

        // no Site node found at rootline
        return null;
    }
}
