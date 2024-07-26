<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

/*
 * This file is part of the Neos.ContentRepository.InMemoryGraph package.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\InMemoryGraph\ContentSubgraph\ContentSubgraph;
use Neos\ContentRepository\InMemoryGraph\ContentSubgraph\ContentSubgraphIdentifier;
use Neos\ContentRepository\InMemoryGraph\ContentSubgraph\HierarchyRelation;
use Neos\ContentRepository\InMemoryGraph\ContentSubgraph\NodeAssignmentRegistry;
use Neos\ContentRepository\InMemoryGraph\ContentSubgraph\ReferenceRelation;
use Neos\ContentRepository\InMemoryGraph\NodeAggregate\Node;
use Neos\ContentRepository\InMemoryGraph\NodeAggregate\NodeAggregate;
use Neos\Flow\Cli\ConsoleOutput;

/**
 * The in-memory content graph
 */
final class InMemoryContentGraph implements ContentGraphInterface
{
    /**
     * @var array|ContentSubgraph[]
     */
    protected $subgraphs;

    /**
     * @var array|Node[]
     */
    protected $nodeIndex;

    /**
     * @var array|NodeAggregate[]
     */
    protected $nodeAggregateIndex;

    /**
     * @param array|ContentSubgraph[] $subgraphs
     * @param array|Node[] $nodes
     * @param array|NodeAggregate[] $nodeAggregates
     * @param NodeAssignmentRegistry $nodeAssignments
     * @param ConsoleOutput|null $output
     */
    public function __construct(array $subgraphs, array $nodes, array $nodeAggregates, NodeAssignmentRegistry $nodeAssignments, ConsoleOutput $output = null)
    {
        $this->subgraphs = $subgraphs;
        $this->nodeAggregateIndex = $nodeAggregates;
        $numberOfNodes = 0;
        $numberOfHierarchyRelations = 0;

        if ($output) {
            $output->outputLine('Assigning hierarchy relations to nodes...');
            $output->progressStart(count($nodes));
        }
        foreach ($nodes as $node) {
            if ($node->getPath() !== '/') {
                foreach ($nodeAssignments->getSubgraphIdentifiersByPathAndNodeIdentifier($node->getPath(), $node->getCacheEntryIdentifier()) as $subgraphIdentifier) {
                    $parentNode = $nodeAssignments->getNodeByPathAndSubgraphIdentifier($node->getParentPath(), $subgraphIdentifier);
                    if ($parentNode) {
                        $hierarchyRelation = new HierarchyRelation(
                            $parentNode,
                            $node,
                            $subgraphs[(string)$subgraphIdentifier],
                            (string)$subgraphIdentifier,
                            $node->getIndex(),
                            $node->getNodeName(),
                            [
                                'accessRoles' => $node->getAccessRoles(),
                                'hidden' => $node->isHidden(),
                                'hiddenBeforeDateTime' => $node->getHiddenBeforeDateTime(),
                                'hiddenAfterDateTime' => $node->getHiddenAfterDateTime(),
                                'hiddenInIndex' => $node->isHiddenInIndex(),
                            ]
                        );
                        $numberOfHierarchyRelations++;
                        $node->registerIncomingHierarchyRelation($hierarchyRelation);
                        $parentNode->registerOutgoingHierarchyRelation($hierarchyRelation);
                    }
                }
            }

            $this->nodeIndex[$node->getCacheEntryIdentifier()] = $node;

            if ($output) {
                $output->progressAdvance();
            }
            $numberOfNodes++;
        }

        if ($output) {
            $output->progressFinish();
            $output->outputLine('Successfully initialized content graph containing ' . count($this->nodeIndex) . ' nodes and ' . $numberOfHierarchyRelations . ' hierarchy relations.');
        }

        if ($output) {
            $output->outputLine('Assigning reference relations to nodes...');
            $output->progressStart(count($nodes));
        }

        $numberOfReferenceRelations = 0;
        foreach ($nodes as $node) {
            if ($node->getPath() !== '/') {
                $numberOfReferenceRelations += $this->createReferenceRelations($node);
            }

            if ($output) {
                $output->progressAdvance();
            }
        }

        if ($output) {
            $output->progressFinish();
            $output->outputLine('Successfully created ' . $numberOfReferenceRelations . ' reference relations.');
        }
    }

    public function createReferenceRelations(Node $sourceNode)
    {
        $numberOfReferenceRelations = 0;
        foreach ($sourceNode->getNodeType()->getProperties() as $referenceName => $propertyConfiguration) {
            if (isset($propertyConfiguration['type'])) {
                if (in_array($propertyConfiguration['type'], ['references', 'reference'])) {
                    $propertyValue = $sourceNode->getNodeData()->getProperty($referenceName);

                    if (!$propertyValue) {
                        $propertyValue = [];
                    } else {
                        if (!is_array($propertyValue)) {
                            $propertyValue = [$propertyValue];
                        }
                    }

                    foreach ($propertyValue as $index => $targetNodeAggregateIdentifier) {
                        $targetNodeAggregate = $this->getNodeAggregate($targetNodeAggregateIdentifier);

                        if ($targetNodeAggregate) {
                            $this->createSingleReferenceRelation($sourceNode, $referenceName, $index, $targetNodeAggregate);
                            $numberOfReferenceRelations++;
                        }
                    }
                }
            }
        }

        return $numberOfReferenceRelations;
    }

    public function createSingleReferenceRelation(
        Node $sourceNode,
        string $referenceName,
        int $index,
        NodeAggregate $targetNodeAggregate
    ) {
        $referenceRelation = new ReferenceRelation($sourceNode, $targetNodeAggregate, $index, $referenceName, []);

        $sourceNode->registerOutgoingReferenceRelation($referenceRelation);

        foreach ($targetNodeAggregate->getNodes() as $targetNodeCandidate) {
            foreach ($targetNodeCandidate->getIncomingHierarchyRelations() as $hierarchyRelation) {
                if ($sourceNode->getIncomingHierarchyRelationInSubgraph($hierarchyRelation->getSubgraph()->getIdentifier())) {
                    $targetNodeCandidate->registerIncomingReferenceRelation($referenceRelation);
                    break;
                }
            }
        }
    }

    public function traverseNodeIndex(callable $callback, bool $includeShadowNodes = false): void
    {
        foreach ($this->nodeIndex as $node) {
            if (!$includeShadowNodes && $node->getNodeData()->isInternal()) {
                continue;
            }
            $continue = $callback($node);
            if ($continue === false) {
                break;
            }
        }
    }

    public function traverseSubgraphs(callable $callback): void
    {
        foreach ($this->subgraphs as $subgraph) {
            $subgraph->traverse($callback);
        }
    }

    /**
     * @return array|Node[]
     */
    public function getNodes(): array
    {
        return $this->nodeIndex;
    }

    public function getNode(string $nodeIdentifier): ?Node
    {
        return $this->nodeIndex[$nodeIdentifier] ?? null;
    }

    public function registerNode(Node $node): void
    {
        $this->nodeIndex[$node->getCacheEntryIdentifier()] = $node;
    }

    public function unregisterNode(string $nodeIdentifier): void
    {
        if (isset($this->nodeIndex[$nodeIdentifier])) {
            unset($this->nodeIndex[$nodeIdentifier]);
        }
    }
    public function traverseNodeAggregateIndex(callable $callback): void
    {
        foreach ($this->nodeAggregateIndex as $nodeAggregate) {
            $callback($nodeAggregate);
        }
    }

    /**
     * @return array|NodeAggregate[]
     */
    public function getNodeAggregates(): array
    {
        return $this->nodeAggregateIndex;
    }

    public function getNodeAggregate(string $nodeAggregateIdentifier): ?NodeAggregate
    {
        return $this->nodeAggregateIndex[$nodeAggregateIdentifier] ?? null;
    }

    public function registerNodeAggregate(NodeAggregate $nodeAggregate): void
    {
        $this->nodeAggregateIndex[(string)$nodeAggregate->getIdentifier()] = $nodeAggregate;
    }

    public function unregisterNodeAggregate(string $nodeAggregateIdentifier): void
    {
        if (isset($this->nodeAggregateIndex[$nodeAggregateIdentifier])) {
            unset($this->nodeAggregateIndex[$nodeAggregateIdentifier]);
        }
    }

    /**
     * @return array|ContentSubgraph[]
     */
    public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }

    public function getSubgraphByIdentifier(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): ?ContentSubgraph
    {
        $subgraphIdentifier = new ContentSubgraphIdentifier((string)$contentStreamIdentifier, $dimensionSpacePoint);

        return $this->subgraphs[(string)$subgraphIdentifier] ?? null;
    }

    public function getContentRepositoryId(): ContentRepositoryId
    {
        // TODO: Implement getContentRepositoryId() method.
    }

    public function getWorkspaceName(): WorkspaceName
    {
        // TODO: Implement getWorkspaceName() method.
    }

    public function getSubgraph(
        \Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        // TODO: Implement getSubgraph() method.
    }

    public function findRootNodeAggregateByType(NodeTypeName $nodeTypeName): NodeAggregate
    {
        // TODO: Implement findRootNodeAggregateByType() method.
    }

    public function findRootNodeAggregates(Filter\FindRootNodeAggregatesFilter $filter,): NodeAggregates
    {
        // TODO: Implement findRootNodeAggregates() method.
    }

    public function findNodeAggregatesByType(NodeTypeName $nodeTypeName): NodeAggregates
    {
        // TODO: Implement findNodeAggregatesByType() method.
    }

    public function findNodeAggregateById(NodeAggregateId $nodeAggregateId): ?NodeAggregate
    {
        // TODO: Implement findNodeAggregateById() method.
    }

    public function findUsedNodeTypeNames(): NodeTypeNames
    {
        // TODO: Implement findUsedNodeTypeNames() method.
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        // TODO: Implement findParentNodeAggregateByChildOriginDimensionSpacePoint() method.
    }

    public function findParentNodeAggregates(NodeAggregateId $childNodeAggregateId): NodeAggregates
    {
        // TODO: Implement findParentNodeAggregates() method.
    }

    public function findChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): NodeAggregates
    {
        // TODO: Implement findChildNodeAggregates() method.
    }

    public function findChildNodeAggregateByName(NodeAggregateId $parentNodeAggregateId, NodeName $name): ?NodeAggregate
    {
        // TODO: Implement findChildNodeAggregateByName() method.
    }

    public function findTetheredChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): NodeAggregates
    {
        // TODO: Implement findTetheredChildNodeAggregates() method.
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(
        NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        // TODO: Implement getDimensionSpacePointsOccupiedByChildNodeName() method.
    }

    public function countNodes(): int
    {
        // TODO: Implement countNodes() method.
    }

    public function getContentStreamId(): ContentStreamId
    {
        // TODO: Implement getContentStreamId() method.
    }
}
