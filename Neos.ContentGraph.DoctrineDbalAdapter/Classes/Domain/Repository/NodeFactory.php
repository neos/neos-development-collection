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
     * @Flow\Inject
     * @var ContentRepository\Repository\NodeDataRepository
     * @todo get rid of this
     */
    protected $nodeDataRepository;

    /**
     * @param array $nodeRow Node Row from projection (neos_contentgraph_node table)
     * @param ContentRepository\Service\Context $context
     * @return ContentRepository\Model\NodeInterface
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

            $legacyDimensionValues = $dimensionSpacePoint->toLegacyDimensionArray();
            $query = $this->nodeDataRepository->createQuery();
            $nodeData = $query->matching(
                $query->logicalAnd([
                    $query->equals('workspace', (string) $contentStreamIdentifier),
                    $query->equals('identifier', $nodeRow['nodeaggregateidentifier']),
                    $query->equals('dimensionsHash', Utility::sortDimensionValueArrayAndReturnDimensionsHash($legacyDimensionValues))
                ])
            )->execute()->getFirst();

            $node = new $className($nodeData, $context);
            $node->nodeType = $nodeType;
            $node->aggregateIdentifier = new ContentRepository\ValueObject\NodeAggregateIdentifier($nodeRow['nodeaggregateidentifier']);
            $node->identifier = new ContentRepository\ValueObject\NodeIdentifier($nodeRow['nodeidentifier']);
            $node->properties = new ContentProjection\PropertyCollection(json_decode($nodeRow['properties'], true));
            if (!array_key_exists('name', $nodeRow)) {
                throw new \Exception('The "name" property was not found in the $nodeRow; you need to include the "name" field in the SQL result.');
            }
            $node->name = $nodeRow['name'];
            $node->nodeTypeName = new ContentRepository\ValueObject\NodeTypeName($nodeRow['nodetypename']);
            #@todo fetch workspace from finder using the content stream identifier
            #$node->workspace = $this->workspaceRepository->findByIdentifier($this->contentStreamIdentifier);
            $node->dimensionSpacePoint = $dimensionSpacePoint;
            $node->contentStreamIdentifier = $contentStreamIdentifier;

            return $node;
        } else {
            // root node
            $subgraphIdentifier = null;
            $nodeData = ($context ? $context->getWorkspace()->getRootNodeData() : null);

            $node = new $className($nodeData, $context);
            $node->nodeType = $nodeType;
            $node->nodeTypeName = new ContentRepository\ValueObject\NodeTypeName($nodeRow['nodetypename']);
            $node->identifier = new ContentRepository\ValueObject\NodeIdentifier($nodeRow['nodeidentifier']);
            #@todo fetch workspace from finder using the content stream identifier
            #$node->workspace = $this->workspaceRepository->findByIdentifier($this->contentStreamIdentifier);

            $node->dimensionSpacePoint = null;

            return $node;
        }
    }
}
