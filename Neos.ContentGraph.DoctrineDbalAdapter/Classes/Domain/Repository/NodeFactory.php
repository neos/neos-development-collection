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
use Neos\ContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
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
     * @throws \Neos\ContentRepository\Exception\NodeConfigurationException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
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

            /* @var $node \Neos\ContentRepository\Domain\Model\NodeInterface */
            $node = new $className(
                new ContentRepository\ValueObject\NodeIdentifier($nodeRow['nodeidentifier']),
                new NodeTypeName($nodeRow['nodetypename']),
                $nodeType,
                $dimensionSpacePoint,
                new ContentRepository\ValueObject\NodeAggregateIdentifier($nodeRow['nodeaggregateidentifier']),
                $contentStreamIdentifier,
                new ContentProjection\PropertyCollection(json_decode($nodeRow['properties'], true)),
                new ContentRepository\ValueObject\NodeName($nodeRow['name']),
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
                $context);

            return $node;
        }
    }
}
