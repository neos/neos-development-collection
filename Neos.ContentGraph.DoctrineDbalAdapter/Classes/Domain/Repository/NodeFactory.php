<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Node;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Context\Parameters\ContextParameters;
use Neos\ContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\ContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Utility;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Now;
use Neos\Neos\Domain\Context\Content\ContentQuery;
use Neos\Neos\Domain\Projection\Site\Site;
use Neos\Neos\Domain\Projection\Site\SiteFinder;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Flow\Security\Context as SecurityContext;


/**
 * Implementation detail of ContentGraph and ContentSubgraph
 *
 * @Flow\Scope("singleton")
 */
final class NodeFactory
{

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;


    /**
     * @Flow\Inject
     * @var SiteFinder
     */
    protected $siteFinder;

    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var ContentRepository\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @param $contentQuery
     * @return array
     */
    public function findNodeForContentQuery(ContentQuery $contentQuery): ContentProjection\NodeInterface
    {
        $inBackend = !$contentQuery->getWorkspaceName()->isLive();
        $workspace = $this->workspaceFinder->findOneByName($contentQuery->getWorkspaceName());
        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $workspace->getCurrentContentStreamIdentifier(),
            $contentQuery->getDimensionSpacePoint()
        );

        $contextParameters = $this->createContextParameters($inBackend);

        $siteNode = $subgraph->findNodeByNodeAggregateIdentifier($contentQuery->getSiteIdentifier());
        // TODO CACHE
        $site = $this->siteFinder->findOneByNodeName(new NodeName($siteNode->getName()));

        $contentContext = $this->createContentContext($contentQuery, $subgraph, $contextParameters, $site);

        $node = $subgraph->findNodeByNodeAggregateIdentifier($contentQuery->getNodeAggregateIdentifier());

        return $node;
    }

    /**
     * @param bool $inBackend
     * @return ContextParameters
     */
    protected function createContextParameters(bool $inBackend): ContextParameters
    {
        return new ContextParameters($this->now, $this->securityContext->getRoles(), $inBackend, $inBackend, $inBackend);
    }

    /**
     * @param ContentQuery $contentQuery
     * @param ContentSubgraphInterface $subgraph
     * @param ContextParameters $contextParameters
     * @return ContentContext
     */
    protected function createContentContext(ContentQuery $contentQuery, ContentSubgraphInterface $subgraph, ContextParameters $contextParameters, Site $site): ContentContext
    {
        return new ContentContext(
            (string)$contentQuery->getWorkspaceName(),
            $contextParameters->getCurrentDateTime(),
            $subgraph->getDimensionSpacePoint()->toLegacyDimensionArray(),
            $subgraph->getDimensionSpacePoint()->getCoordinates(),
            $contextParameters->isInvisibleContentShown(),
            $contextParameters->isRemovedContentShown(),
            $contextParameters->isInaccessibleContentShown(),
            $site,
            null,
            $contentQuery->getRootNodeIdentifier(),
            $subgraph,
            $contextParameters
        );
    }


    /**
     * @param array $nodeRow Node Row from projection (neos_contentgraph_node table)
     * @return ContentProjection\NodeInterface
     * @throws \Exception
     * @throws \Neos\ContentRepository\Exception\NodeConfigurationException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function mapNodeRowToNode(array $nodeRow, ContentSubgraphInterface $subgraph): ContentProjection\NodeInterface
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeRow['nodetypename']);
        $className = $nodeType->getNodeInterfaceImplementationClassName();

        $referenceProperties = array_keys(array_filter($nodeType->getProperties(), function($propertyConfiguration) {return $propertyConfiguration['type'] == 'reference';}));
        $referencesProperties = array_keys(array_filter($nodeType->getProperties(), function($propertyConfiguration) {return $propertyConfiguration['type'] == 'references';}));

        // $serializedSubgraphIdentifier is empty for the root node
        if (!empty($nodeRow['dimensionspacepointhash'])) {
            // NON-ROOT case
            if (!array_key_exists('contentstreamidentifier', $nodeRow)) {
                throw new \Exception('The "contentstreamidentifier" property was not found in the $nodeRow; you need to include the "contentstreamidentifier" field in the SQL result.');
            }
            if (!array_key_exists('dimensionspacepoint', $nodeRow)) {
                throw new \Exception('The "dimensionspacepoint" property was not found in the $nodeRow; you need to include the "dimensionspacepoint" field in the SQL result.');
            }

            $contentStreamIdentifier = new ContentRepository\ValueObject\ContentStreamIdentifier($nodeRow['contentstreamidentifier']);
            // FIXME Move to DimensionSpacePoint::fromJson
            $dimensionSpacePoint = new ContentRepository\ValueObject\DimensionSpacePoint(json_decode($nodeRow['dimensionspacepoint'], true)['coordinates']);

            $contentSubgraph = $context ? $context->getContentSubgraph() : null;
            $nodeIdentifier = new ContentRepository\ValueObject\NodeIdentifier($nodeRow['nodeidentifier']);

            /* @var $node \Neos\ContentRepository\Domain\Model\NodeInterface */
            $node = new $className(
                $nodeIdentifier,
                new NodeTypeName($nodeRow['nodetypename']),
                $nodeType,
                $dimensionSpacePoint,
                new ContentRepository\ValueObject\NodeAggregateIdentifier($nodeRow['nodeaggregateidentifier']),
                $contentStreamIdentifier,
                new ContentProjection\PropertyCollection(json_decode($nodeRow['properties'], true), $referenceProperties, $referencesProperties, $nodeIdentifier, $contentSubgraph),
                new ContentRepository\ValueObject\NodeName($nodeRow['name']),
                $nodeRow['hidden'],
                $context
            );

            if (!array_key_exists('name', $nodeRow)) {
                throw new \Exception('The "name" property was not found in the $nodeRow; you need to include the "name" field in the SQL result.');
            }
            return $node;
        } else {
            /* @var $node \Neos\ContentRepository\Domain\Model\NodeInterface */
            $node = new $className(
                new ContentRepository\ValueObject\NodeIdentifier($nodeRow['nodeidentifier']),
                new NodeTypeName($nodeRow['nodetypename']),
                $nodeType,
                null,
                null,
                null,
                null,
                null,
                false,
                $context);

            return $node;
        }
    }
}
