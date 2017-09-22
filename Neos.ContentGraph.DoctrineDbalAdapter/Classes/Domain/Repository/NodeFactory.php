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
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\ContentRepository\Utility;
use Neos\Flow\Annotations as Flow;


/**
 * Implementation detail of ContentGraph and ContentSubgraph
 *
 * @Flow\Scope("singleton")
 */
final class NodeFactory
{

    /**
     * @Flow\Inject
     * @var ContentRepository\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @param array $nodeRow Node Row from projection (neos_contentgraph_node table)
     * @param ContentRepository\Service\Context $context
     * @return ContentRepository\Model\NodeInterface
     * @throws \Exception
     */
    public function mapNodeRowToNode(array $nodeRow, ContentRepository\Service\Context $context = null): ContentRepository\Model\NodeInterface
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeRow['nodetypename']);
        $className = $nodeType->getNodeInterfaceImplementationClassName();

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

            $node = new $className(null, $context);
            $node->nodeType = $nodeType;
            $node->aggregateIdentifier = new ContentRepository\ValueObject\NodeAggregateIdentifier($nodeRow['nodeaggregateidentifier']);
            $node->identifier = new ContentRepository\ValueObject\NodeIdentifier($nodeRow['nodeidentifier']);
            $node->properties = new ContentProjection\PropertyCollection(json_decode($nodeRow['properties'], true));
            if (!array_key_exists('name', $nodeRow)) {
                throw new \Exception('The "name" property was not found in the $nodeRow; you need to include the "name" field in the SQL result.');
            }
            $node->name = $nodeRow['name'];
            // $node->index = (int)$nodeRow['position'];
            $node->nodeTypeName = new ContentRepository\ValueObject\NodeTypeName($nodeRow['nodetypename']);
            // TODO Add a projection from contentStreamIdentifier to workspaceName, join to the edges for less queries !!! Or just add the workspace name to the edge (more duplicated data).
            // $node->workspace = $this->workspaceRepository->findByIdentifier($this->contentStreamIdentifier);
            $node->dimensionSpacePoint = $dimensionSpacePoint;
            $node->contentStreamIdentifier = $contentStreamIdentifier;

            return $node;
        } else {
            // root node
            $subgraphIdentifier = null;
            $node = new $className(null, $context);
            $node->nodeType = $nodeType;
            $node->nodeTypeName = new ContentRepository\ValueObject\NodeTypeName($nodeRow['nodetypename']);
            $node->identifier = new ContentRepository\ValueObject\NodeIdentifier($nodeRow['nodeidentifier']);
            // TODO Add a projection from contentStreamIdentifier to workspaceName, join to the edges for less queries !!! Or just add the workspace name to the edge (more duplicated data).
            // $node->workspace = $this->workspaceRepository->findByIdentifier($this->contentStreamIdentifier);

            $node->dimensionSpacePoint = null;

            return $node;
        }
    }
}
