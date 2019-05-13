<?php
declare(strict_types=1);

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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Exception\NodeConfigurationException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;

/**
 * Implementation detail of ContentGraph and ContentSubgraph
 *
 * @Flow\Scope("singleton")
 */
final class NodeFactory
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;


    /**
     * @param array $nodeRow Node Row from projection (neos_contentgraph_node table)
     * @return NodeInterface
     * @throws NodeConfigurationException
     * @throws NodeTypeNotFoundException
     */
    public function mapNodeRowToNode(array $nodeRow): NodeInterface
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeRow['nodetypename']);
        $className = $this->getNodeInterfaceImplementationClassName($nodeType);

        $contentStreamIdentifier = ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']);
        $originDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']);

        $properties = json_decode($nodeRow['properties'], true);

        // Reference and References "are no properties" anymore by definition; so Node does not know
        // anything about it.
        $properties = array_filter($properties, function ($propertyName) use ($nodeType) {
            $propertyType = $nodeType->getPropertyType($propertyName);
            return $propertyType !== 'reference' && $propertyType !== 'references';
        }, ARRAY_FILTER_USE_KEY);

        $propertyCollection = new ContentProjection\PropertyCollection($properties);

        /* @var NodeInterface $node */
        $node = new $className(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']),
            $originDimensionSpacePoint,
            NodeTypeName::fromString($nodeRow['nodetypename']),
            $nodeType,
            isset($nodeRow['name']) ? NodeName::fromString($nodeRow['name']) : null,
            $propertyCollection,
            NodeAggregateClassification::fromString($nodeRow['classification'])
        );

        return $node;
    }

    /**
     * @param array $nodeRows
     * @return ContentProjection\NodeAggregate|null
     * @throws NodeConfigurationException
     * @throws NodeTypeNotFoundException
     */
    public function mapNodeRowsToNodeAggregate(array $nodeRows): ?ContentProjection\NodeAggregate
    {
        if (empty($nodeRows)) {
            return null;
        }

        $rawNodeAggregateIdentifier = '';
        $rawNodeTypeName = '';
        $rawNodeName = '';
        $rawNodeAggregateClassification = '';
        $occupiedDimensionSpacePoints = [];
        $nodesByOccupiedDimensionSpacePoints = [];
        $coveredDimensionSpacePoints = [];
        $nodesByCoveredDimensionSpacePoints = [];
        $coverageByOccupants = [];
        $occupationByCovering = [];
        $disabledDimensionSpacePoints = [];

        foreach ($nodeRows as $nodeRow) {
            // A node can occupy exactly one DSP and cover multiple ones...
            $occupiedDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']);
            if (!isset($nodesByOccupiedDimensionSpacePoints[$occupiedDimensionSpacePoint->getHash()])) {
                // ... so we handle occupation exactly once ...
                $nodesByOccupiedDimensionSpacePoints[$occupiedDimensionSpacePoint->getHash()] = $this->mapNodeRowToNode($nodeRow);
                $occupiedDimensionSpacePoints[] = $occupiedDimensionSpacePoint;
                $rawNodeAggregateIdentifier = $rawNodeAggregateIdentifier ?: $nodeRow['nodeaggregateidentifier'];
                $rawNodeTypeName = $rawNodeTypeName ?: $nodeRow['nodetypename'];
                $rawNodeName = $rawNodeName ?: $nodeRow['name'];
                $rawNodeAggregateClassification = $rawNodeAggregateClassification ?: $nodeRow['classification'];
            }
            // ... and coverage always ...
            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['covereddimensionspacepoint']);
            $coveredDimensionSpacePoints[$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;

            $coverageByOccupants[$occupiedDimensionSpacePoint->getHash()][] = $coveredDimensionSpacePoint;
            $occupationByCovering[$coveredDimensionSpacePoint->getHash()] = $occupiedDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoints[$coveredDimensionSpacePoint->getHash()] = $nodesByOccupiedDimensionSpacePoints[$occupiedDimensionSpacePoint->getHash()];
            // ... as we do for disabling
            if (isset($nodeRow['disableddimensionspacepointhash'])) {
                $disabledDimensionSpacePoints[$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            }
        }

        foreach ($coverageByOccupants as &$coverage) {
            $coverage = new DimensionSpacePointSet($coverage);
        }

        return new ContentProjection\NodeAggregate(
            NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier),
            NodeAggregateClassification::fromString($rawNodeAggregateClassification),
            NodeTypeName::fromString($rawNodeTypeName),
            $rawNodeName ? NodeName::fromString($rawNodeName) : null,
            new DimensionSpacePointSet($occupiedDimensionSpacePoints),
            $nodesByOccupiedDimensionSpacePoints,
            $coverageByOccupants,
            new DimensionSpacePointSet($coveredDimensionSpacePoints),
            $nodesByCoveredDimensionSpacePoints,
            $occupationByCovering,
            new DimensionSpacePointSet($disabledDimensionSpacePoints)
        );
    }

    /**
     * @param array $nodeRows
     * @return array|ContentProjection\NodeAggregate[]
     * @throws NodeConfigurationException
     * @throws NodeTypeNotFoundException
     */
    public function mapNodeRowsToNodeAggregates(array $nodeRows): array
    {
        $nodeAggregates = [];
        $nodeTypeNames = [];
        $nodeNames = [];
        $occupiedDimensionSpacePointsByNodeAggregate = [];
        $nodesByOccupiedDimensionSpacePointsByNodeAggregate = [];
        $coveredDimensionSpacePointsByNodeAggregate = [];
        $nodesByCoveredDimensionSpacePointsByNodeAggregate = [];
        $classificationByNodeAggregate = [];
        $coverageByOccupantsByNodeAggregate = [];
        $occupationByCoveringByNodeAggregate = [];
        $disabledDimensionSpacePointsByNodeAggregate = [];

        foreach ($nodeRows as $nodeRow) {
            // A node can occupy exactly one DSP and cover multiple ones...
            $rawNodeAggregateIdentifier = $nodeRow['nodeaggregateidentifier'];
            $occupiedDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']);
            if (!isset($nodesByOccupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][$occupiedDimensionSpacePoint->getHash()])) {
                // ... so we handle occupation exactly once ...
                $nodesByOccupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][$occupiedDimensionSpacePoint->getHash()] = $this->mapNodeRowToNode($nodeRow);
                $occupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][] = $occupiedDimensionSpacePoint;
                $nodeTypeNames[$rawNodeAggregateIdentifier] = $nodeTypeNames[$rawNodeAggregateIdentifier] ?? NodeTypeName::fromString($nodeRow['nodetypename']);
                $nodeNames[$rawNodeAggregateIdentifier] = $nodeNames[$rawNodeAggregateIdentifier] ?? ($nodeRow['name'] ? NodeName::fromString($nodeRow['name']) : null);
                $classificationByNodeAggregate[$rawNodeAggregateIdentifier] = $classificationByNodeAggregate[$rawNodeAggregateIdentifier] ?? NodeAggregateClassification::fromString($nodeRow['classification']);
            }
            // ... and coverage always ...
            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['covereddimensionspacepoint']);
            $coverageByOccupantsByNodeAggregate[$rawNodeAggregateIdentifier][$occupiedDimensionSpacePoint->getHash()][] = $coveredDimensionSpacePoint;
            $occupationByCoveringByNodeAggregate[$rawNodeAggregateIdentifier][$coveredDimensionSpacePoint->getHash()] = $occupiedDimensionSpacePoint;

            $coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][] = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][$coveredDimensionSpacePoint->getHash()]
                = $nodesByOccupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][$occupiedDimensionSpacePoint->getHash()];

            // ... as we do for disabling
            if (isset($nodeRow['disableddimensionspacepointhash'])) {
                $disabledDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            }
        }


        foreach ($coverageByOccupantsByNodeAggregate as &$coverageByOccupants) {
            foreach ($coverageByOccupants as &$coverage) {
                $coverage = new DimensionSpacePointSet($coverage);
            }
        }

        foreach ($nodesByOccupiedDimensionSpacePointsByNodeAggregate as $rawNodeAggregateIdentifier => $nodes) {
            $nodeAggregates[$rawNodeAggregateIdentifier] = new ContentProjection\NodeAggregate(
                NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier),
                $classificationByNodeAggregate[$rawNodeAggregateIdentifier],
                $nodeTypeNames[$rawNodeAggregateIdentifier],
                $nodeNames[$rawNodeAggregateIdentifier],
                new DimensionSpacePointSet($occupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier]),
                $nodesByOccupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier],
                $coverageByOccupantsByNodeAggregate[$rawNodeAggregateIdentifier],
                new DimensionSpacePointSet($coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier]),
                $nodesByCoveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier],
                $occupationByCoveringByNodeAggregate[$rawNodeAggregateIdentifier],
                new DimensionSpacePointSet($disabledDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier] ?? [])
            );
        }

        return $nodeAggregates;
    }

    /**
     * @param NodeType $nodeType
     * @return string
     * @throws NodeConfigurationException
     */
    protected function getNodeInterfaceImplementationClassName(NodeType $nodeType): string
    {
        $customClassName = $nodeType->getConfiguration('class');
        if (!empty($customClassName)) {
            if (!class_exists($customClassName)) {
                throw new NodeConfigurationException('The configured implementation class name "' . $customClassName . '" for NodeType "' . $nodeType . '" does not exist.', 1505805774);
            } elseif (!$this->reflectionService->isClassImplementationOf($customClassName, NodeInterface::class)) {
                if ($this->reflectionService->isClassImplementationOf($customClassName, \Neos\ContentRepository\Domain\Model\NodeInterface::class)) {
                    throw new NodeConfigurationException('The configured implementation class name "' . $customClassName . '" for NodeType "' . $nodeType. '" inherits from the OLD (pre-event-sourced) NodeInterface; which is not supported anymore. Your custom Node class now needs to implement ' . NodeInterface::class . '.', 1520069750);
                }
                throw new NodeConfigurationException('The configured implementation class name "' . $customClassName . '" for NodeType "' . $nodeType. '" does not inherit from ' . NodeInterface::class . '.', 1406884014);
            }
            return $customClassName;
        } else {
            return $this->objectManager->getClassNameByObjectName(NodeInterface::class);
        }
    }
}
