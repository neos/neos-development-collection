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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

#[Flow\Scope('singleton')]
final class SiteNodeUtility
{
    public function __construct(
        private readonly NodeAccessorManager $nodeAccessorManager,
        private readonly DomainRepository $domainRepository,
        private readonly SiteRepository $siteRepository
    )
    {
    }

    public function findSiteNode(NodeInterface $node): NodeInterface
    {
        $previousNode = null;
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getSubgraphIdentity()
        );
        do {
            if ($node->getNodeType()->isOfType('Neos.Neos:Sites')) {
                // the Site node is the one one level underneath the "Sites" node.
                if (is_null($previousNode)) {
                    break;
                }
                return $previousNode;
            }
            $previousNode = $node;
        } while ($node = $nodeAccessor->findParentNode($node));

        // no Site node found at rootline
        throw new \RuntimeException('No site node found!');
    }

    public function findCurrentSiteNode(
        ContentRepositoryIdentifier $contentRepositoryIdentifier,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): NodeInterface
    {
        $domain = $this->domainRepository->findOneByActiveRequest();
        $site = $domain
            ? $domain->getSite()
            : $this->siteRepository->findDefault();

        if ($site instanceof Site) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                new ContentSubgraphIdentity(
                    $contentRepositoryIdentifier,
                    $contentStreamIdentifier,
                    $dimensionSpacePoint,
                    $visibilityConstraints,
                )
            );

            $sitesNode = $nodeAccessor->findRootNodeByType(NodeTypeName::fromString('Neos.Neos:Sites'));
            $siteNode = $nodeAccessor->findChildNodeConnectedThroughEdgeName(
                $sitesNode,
                $site->getNodeName()->toNodeName()
            );
            if ($siteNode instanceof NodeInterface) {
                return $siteNode;
            }
        }

        throw new \RuntimeException('No site node found!');
    }
}
