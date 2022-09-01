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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

#[Flow\Scope('singleton')]
final class SiteNodeUtility
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly DomainRepository $domainRepository,
        private readonly SiteRepository $siteRepository
    ) {
    }

    public function findSiteNode(Node $node): Node
    {
        $previousNode = null;
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        do {
            if ($node->nodeType->isOfType('Neos.Neos:Sites')) {
                // the Site node is the one one level underneath the "Sites" node.
                if (is_null($previousNode)) {
                    break;
                }
                return $previousNode;
            }
            $previousNode = $node;
        } while ($node = $subgraph->findParentNode($node->nodeAggregateId));

        // no Site node found at rootline
        throw new \RuntimeException('No site node found!');
    }

    public function findCurrentSiteNode(
        ContentRepositoryId $contentRepositoryIdentifier,
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): Node {
        $domain = $this->domainRepository->findOneByActiveRequest();
        $site = $domain
            ? $domain->getSite()
            : $this->siteRepository->findDefault();

        if ($site instanceof Site) {
            $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);
            $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $visibilityConstraints,
            );

            $rootNodeAggregate = $contentRepository->getContentGraph()
                ->findRootNodeAggregateByType(
                    $contentStreamIdentifier,
                    NodeTypeName::fromString('Neos.Neos:Sites')
                );
            $sitesNode = $subgraph->findNodeByNodeAggregateId($rootNodeAggregate->nodeAggregateId);
            if ($sitesNode) {
                $siteNode = $subgraph->findChildNodeConnectedThroughEdgeName(
                    $sitesNode->nodeAggregateId,
                    $site->getNodeName()->toNodeName()
                );
                if ($siteNode instanceof Node) {
                    return $siteNode;
                }
            }
        }

        throw new \RuntimeException('No site node found!');
    }
}
