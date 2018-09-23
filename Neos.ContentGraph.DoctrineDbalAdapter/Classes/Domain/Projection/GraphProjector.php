<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event;
use Neos\EventSourcedContentRepository\Domain as ContentRepository;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodePropertyWasSet;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeWasHidden;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeWasShown;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeReferencesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The alternate reality-aware graph projector for the Doctrine backend
 *
 * @Flow\Scope("singleton")
 */
class GraphProjector implements ProjectorInterface
{
    const RELATION_DEFAULT_OFFSET = 128;

    /**
     * @Flow\Inject
     * @var ProjectionContentGraph
     */
    protected $projectionContentGraph;

    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     * @Flow\Signal
     */
    public function emitProjectionUpdated()
    {
    }

    /**
     * @throws \Exception
     */
    public function reset(): void
    {
        $this->getDatabaseConnection()->transactional(function () {
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_node');
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_hierarchyrelation');
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_referencerelation');
        });
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->projectionContentGraph->isEmpty();
    }

    /**
     * @param Event\RootNodeWasCreated $event
     * @throws \Exception
     */
    final public function whenRootNodeWasCreated(Event\RootNodeWasCreated $event)
    {
        $nodeRelationAnchorPoint = new NodeRelationAnchorPoint();
        $node = new Node(
            $nodeRelationAnchorPoint,
            $event->getNodeIdentifier(),
            null,
            null,
            null,
            [],
            $event->getNodeTypeName()
        );

        $this->transactional(function () use ($node) {
            $node->addToDatabase($this->getDatabaseConnection());
        });
    }

    /**
     * @param Event\NodeAggregateWithNodeWasCreated $event
     * @throws \Exception
     */
    final public function whenNodeAggregateWithNodeWasCreated(Event\NodeAggregateWithNodeWasCreated $event)
    {
        $this->transactional(function () use ($event) {
            $this->createNodeWithHierarchy(
                $event->getContentStreamIdentifier(),
                $event->getNodeAggregateIdentifier(),
                $event->getNodeTypeName(),
                $event->getNodeIdentifier(),
                $event->getParentNodeIdentifier(),
                $event->getDimensionSpacePoint(),
                $event->getVisibleDimensionSpacePoints(),
                $event->getPropertyDefaultValuesAndTypes(),
                $event->getNodeName()
            );
        });
    }

    /**
     * @param Event\NodeWasAddedToAggregate $event
     * @throws \Exception
     */
    final public function whenNodeWasAddedToAggregate(Event\NodeWasAddedToAggregate $event)
    {
        $this->transactional(function () use ($event) {
            $contentStreamIdentifier = $event->getContentStreamIdentifier();
            $nodeAggregateIdentifier = $event->getNodeAggregateIdentifier();

            $this->createNodeWithHierarchy(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $event->getNodeTypeName(),
                $event->getNodeIdentifier(),
                $event->getParentNodeIdentifier(),
                $event->getDimensionSpacePoint(),
                $event->getVisibleDimensionSpacePoints(),
                $event->getPropertyDefaultValuesAndTypes(),
                $event->getNodeName()
            );
        });
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeIdentifier $parentNodeIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param DimensionSpacePointSet $visibleDimensionSpacePoints
     * @param array $propertyDefaultValuesAndTypes
     * @param NodeName $nodeName
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createNodeWithHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        NodeIdentifier $nodeIdentifier,
        NodeIdentifier $parentNodeIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        DimensionSpacePointSet $visibleDimensionSpacePoints,
        array $propertyDefaultValuesAndTypes,
        NodeName $nodeName
    )
    {
        $nodeRelationAnchorPoint = new NodeRelationAnchorPoint();
        $node = new Node(
            $nodeRelationAnchorPoint,
            $nodeIdentifier,
            $nodeAggregateIdentifier,
            $dimensionSpacePoint->jsonSerialize(),
            $dimensionSpacePoint->getHash(),
            array_map(function (ContentRepository\ValueObject\PropertyValue $propertyValue) {
                return $propertyValue->getValue();
            }, $propertyDefaultValuesAndTypes),
            $nodeTypeName
        );

        // reconnect parent relations
        $missingParentRelations = $visibleDimensionSpacePoints->getPoints();
        $existingParentRelations = $this->projectionContentGraph->findInboundHierarchyRelationsForNodeAggregate(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $visibleDimensionSpacePoints
        );
        foreach ($existingParentRelations as $existingParentRelation) {
            $existingParentRelation->assignNewChildNode($nodeRelationAnchorPoint, $this->getDatabaseConnection());
            unset($missingParentRelations[$existingParentRelation->dimensionSpacePointHash]);
        }

        if (!empty($missingParentRelations)) {
            // add yet missing parent relations
            $designatedParentNode = $this->projectionContentGraph->getNode($parentNodeIdentifier, $contentStreamIdentifier);
            $parentIsRootNode = count($this->projectionContentGraph->findInboundHierarchyRelationsForNode($designatedParentNode->relationAnchorPoint, $contentStreamIdentifier)) === 0;
            foreach ($missingParentRelations as $dimensionSpacePoint) {
                if ($parentIsRootNode) {
                    $parentNode = $designatedParentNode;
                } else {
                    $parentNode = $this->projectionContentGraph->getNodeInAggregate(
                        $designatedParentNode->nodeAggregateIdentifier,
                        $contentStreamIdentifier,
                        $dimensionSpacePoint
                    );
                }

                $this->connectHierarchy(
                    $parentNode->relationAnchorPoint,
                    $nodeRelationAnchorPoint,
                    null,
                    $nodeName,
                    $contentStreamIdentifier,
                    new DimensionSpacePointSet([$dimensionSpacePoint])
                );
            }
        }

        // reconnect child relations
        $existingChildRelations = $this->projectionContentGraph->findOutboundHierarchyRelationsForNodeAggregate(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $visibleDimensionSpacePoints
        );
        foreach ($existingChildRelations as $existingChildRelation) {
            $existingChildRelation->assignNewParentNode($nodeRelationAnchorPoint, $this->getDatabaseConnection());
        }

        $node->addToDatabase($this->getDatabaseConnection());
    }

    /**
     * @param NodeRelationAnchorPoint $parentNodeAnchorPoint
     * @param NodeRelationAnchorPoint $childNodeAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingNodeAnchorPoint
     * @param NodeName|null $relationName
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function connectHierarchy(
        NodeRelationAnchorPoint $parentNodeAnchorPoint,
        NodeRelationAnchorPoint $childNodeAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchorPoint,
        NodeName $relationName = null,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): void
    {
        foreach ($dimensionSpacePointSet->getPoints() as $dimensionSpacePoint) {
            $position = $this->getRelationPosition(
                $parentNodeAnchorPoint,
                null,
                $succeedingSiblingNodeAnchorPoint,
                $contentStreamIdentifier,
                $dimensionSpacePoint
            );

            $hierarchyRelation = new HierarchyRelation(
                $parentNodeAnchorPoint,
                $childNodeAnchorPoint,
                $relationName,
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $dimensionSpacePoint->getHash(),
                $position
            );

            $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
        }
    }

    /**
     * @param NodeRelationAnchorPoint|null $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $childAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getRelationPosition(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): int
    {
        $position = $this->projectionContentGraph->determineHierarchyRelationPosition($parentAnchorPoint, $childAnchorPoint, $succeedingSiblingAnchorPoint, $contentStreamIdentifier, $dimensionSpacePoint);

        if ($position % 2 !== 0) {
            $position = $this->getRelationPositionAfterRecalculation($parentAnchorPoint, $childAnchorPoint, $succeedingSiblingAnchorPoint, $contentStreamIdentifier, $dimensionSpacePoint);
        }

        return $position;
    }

    /**
     * @param NodeRelationAnchorPoint|null $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $childAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getRelationPositionAfterRecalculation(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): int
    {
        if (!$childAnchorPoint && !$parentAnchorPoint) {
            throw new \InvalidArgumentException('You must either specify a parent or child node anchor to get relation positions after recalculation.', 1519847858);
        }
        $offset = 0;
        $position = 0;
        $hierarchyRelations = $parentAnchorPoint
            ? $this->projectionContentGraph->getOutboundHierarchyRelationsForNodeAndSubgraph($parentAnchorPoint, $contentStreamIdentifier, $dimensionSpacePoint)
            : $this->projectionContentGraph->getInboundHierarchyRelationsForNodeAndSubgraph($childAnchorPoint, $contentStreamIdentifier, $dimensionSpacePoint);

        foreach ($hierarchyRelations as $relation) {
            $offset += self::RELATION_DEFAULT_OFFSET;
            if ($succeedingSiblingAnchorPoint && $relation->childNodeAnchor === (string)$succeedingSiblingAnchorPoint) {
                $position = $offset;
                $offset += self::RELATION_DEFAULT_OFFSET;
            }
            $relation->assignNewPosition($offset, $this->getDatabaseConnection());
        }

        return $position;
    }

    /**
     * @param ContentRepository\Context\ContentStream\Event\ContentStreamWasForked $event
     * @throws \Exception
     */
    public function whenContentStreamWasForked(ContentRepository\Context\ContentStream\Event\ContentStreamWasForked $event)
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeUpdate('
                INSERT INTO neos_contentgraph_hierarchyrelation (
                  parentnodeanchor,
                  childnodeanchor,
                  `name`,
                  position,
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  contentstreamidentifier
                )
                SELECT
                  h.parentnodeanchor,
                  h.childnodeanchor, 
                  h.name,
                  h.position,
                  h.dimensionspacepoint,
                  h.dimensionspacepointhash, 
                  "' . (string)$event->getContentStreamIdentifier() . '" AS contentstreamidentifier
                FROM
                    neos_contentgraph_hierarchyrelation h
                    WHERE h.contentstreamidentifier = :sourceContentStreamIdentifier
            ', [
                'sourceContentStreamIdentifier' => (string)$event->getSourceContentStreamIdentifier()
            ]);
        });
    }

    /**
     * @param NodePropertyWasSet $event
     * @throws \Exception
     */
    public function whenNodePropertyWasSet(NodePropertyWasSet $event)
    {
        $this->transactional(function () use ($event) {
            $this->updateNodeWithCopyOnWrite($event, function (Node $node) use ($event) {
                $node->properties[$event->getPropertyName()] = $event->getValue()->getValue();
            });
        });
    }

    public function whenNodeReferencesWereSet(NodeReferencesWereSet $event)
    {
        $this->transactional(function () use ($event) {
            $this->updateNodeWithCopyOnWrite($event, function (Node $node) use ($event) {
            });
            $nodeAnchorPoint = $this->projectionContentGraph->getAnchorPointForNodeAndContentStream($event->getNodeIdentifier(), $event->getContentStreamIdentifier());

            // remove old
            $this->getDatabaseConnection()->delete('neos_contentgraph_referencerelation', [
                'nodeanchorpoint' => $nodeAnchorPoint,
                'name' => $event->getPropertyName()
            ]);

            // set new
            foreach ($event->getDestinationNodeAggregateIdentifiers() as $position => $destinationNodeIdentifier) {
                $this->getDatabaseConnection()->insert('neos_contentgraph_referencerelation', [
                    'name' => $event->getPropertyName(),
                    'position' => $position,
                    'nodeanchorpoint' => $nodeAnchorPoint,
                    'destinationnodeaggregateidentifier' => $destinationNodeIdentifier,
                ]);
            }
        });
    }

    /**
     * @param NodeWasHidden $event
     * @throws \Exception
     */
    public function whenNodeWasHidden(NodeWasHidden $event)
    {
        $this->transactional(function () use ($event) {
            $this->updateNodeWithCopyOnWrite($event, function (Node $node) use ($event) {
                $node->hidden = true;
            });
        });
    }

    /**
     * @param NodeWasShown $event
     * @throws \Exception
     */
    public function whenNodeWasShown(NodeWasShown $event)
    {
        $this->transactional(function () use ($event) {
            $this->updateNodeWithCopyOnWrite($event, function (Node $node) {
                $node->hidden = false;
            });
        });
    }

    /**
     * @param \Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeSpecializationWasCreated $event
     * @throws \Exception
     */
    public function whenNodeSpecializationWasCreated(ContentRepository\Context\NodeAggregate\Event\NodeSpecializationWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->projectionContentGraph->getNodeInAggregate($event->getNodeAggregateIdentifier(), $event->getContentStreamIdentifier(), $event->getSourceDimensionSpacePoint());

            $specializedNodeRelationAnchorPoint = new NodeRelationAnchorPoint();
            $specializedNode = new Node(
                $specializedNodeRelationAnchorPoint,
                $event->getSpecializationIdentifier(),
                $sourceNode->nodeAggregateIdentifier,
                $event->getSpecializationLocation()->jsonSerialize(),
                $event->getSpecializationLocation()->getHash(),
                $sourceNode->properties,
                $sourceNode->nodeTypeName
            );
            $specializedNode->addToDatabase($this->getDatabaseConnection());

            foreach ($this->projectionContentGraph->findInboundHierarchyRelationsForNode(
                $sourceNode->relationAnchorPoint,
                $event->getContentStreamIdentifier(),
                $event->getSpecializationVisibility()
            ) as $hierarchyRelation) {
                $hierarchyRelation->assignNewChildNode($specializedNodeRelationAnchorPoint, $this->getDatabaseConnection());
            }
            foreach ($this->projectionContentGraph->findOutboundHierarchyRelationsForNode(
                $sourceNode->relationAnchorPoint,
                $event->getContentStreamIdentifier(),
                $event->getSpecializationVisibility()
            ) as $hierarchyRelation) {
                $hierarchyRelation->assignNewParentNode($specializedNodeRelationAnchorPoint, $this->getDatabaseConnection());
            }
        });
    }

    /**
     * @param \Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeGeneralizationWasCreated $event
     * @throws \Exception
     */
    public function whenNodeGeneralizationWasCreated(ContentRepository\Context\NodeAggregate\Event\NodeGeneralizationWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->projectionContentGraph->getNodeInAggregate($event->getNodeAggregateIdentifier(), $event->getContentStreamIdentifier(), $event->getSourceDimensionSpacePoint());
            $sourceHierarchyRelation = $this->projectionContentGraph->findInboundHierarchyRelationsForNode(
                    $sourceNode->relationAnchorPoint,
                    $event->getContentStreamIdentifier(),
                    new DimensionSpacePointSet([$event->getSourceDimensionSpacePoint()])
                )[$event->getSourceDimensionSpacePoint()->getHash()] ?? null;
            if (is_null($sourceHierarchyRelation)) {
                throw new \Exception('Seems someone tried to generalize a root node and I don\'t have a proper name yet', 1519995795);
            }

            $generalizedNodeRelationAnchorPoint = new NodeRelationAnchorPoint();
            $generalizedNode = new Node(
                $generalizedNodeRelationAnchorPoint,
                $event->getGeneralizationIdentifier(),
                $sourceNode->nodeAggregateIdentifier,
                $event->getGeneralizationLocation()->jsonSerialize(),
                $event->getGeneralizationLocation()->getHash(),
                $sourceNode->properties,
                $sourceNode->nodeTypeName
            );
            $generalizedNode->addToDatabase($this->getDatabaseConnection());

            foreach ($event->getGeneralizationVisibility()->getPoints() as $newRelationDimensionSpacePoint) {
                $newHierarchyRelation = new HierarchyRelation(
                    $sourceHierarchyRelation->parentNodeAnchor,
                    $generalizedNodeRelationAnchorPoint,
                    $sourceHierarchyRelation->name,
                    $event->getContentStreamIdentifier(),
                    $newRelationDimensionSpacePoint,
                    $newRelationDimensionSpacePoint->getHash(),
                    $this->getRelationPosition(
                        $sourceHierarchyRelation->parentNodeAnchor,
                        $generalizedNodeRelationAnchorPoint,
                        null, // todo: find proper sibling
                        $event->getContentStreamIdentifier(),
                        $newRelationDimensionSpacePoint
                    )
                );
                $newHierarchyRelation->addToDatabase($this->getDatabaseConnection());
            }
        });
    }

    public function whenNodeInAggregateWasTranslated(Event\NodeInAggregateWasTranslated $event)
    {
        $this->transactional(function () use ($event) {
            $childNodeRelationAnchorPoint = new NodeRelationAnchorPoint();

            $sourceNode = $this->projectionContentGraph->getNodeByNodeIdentifierAndContentStream($event->getSourceNodeIdentifier(), $event->getContentStreamIdentifier());
            if ($sourceNode === null) {
                // TODO Log error
                return;
            }

            $translatedNode = new Node(
                $childNodeRelationAnchorPoint,
                $event->getDestinationNodeIdentifier(),
                $sourceNode->nodeAggregateIdentifier,
                $event->getDimensionSpacePoint()->jsonSerialize(),
                $event->getDimensionSpacePoint()->getHash(),
                $sourceNode->properties,
                $sourceNode->nodeTypeName
            );
            $parentNode = $this->projectionContentGraph->getNodeByNodeIdentifierAndContentStream($event->getDestinationParentNodeIdentifier(), $event->getContentStreamIdentifier());
            if ($parentNode === null) {
                // TODO Log error
                return;
            }

            $translatedNode->addToDatabase($this->getDatabaseConnection());
            $this->connectHierarchy(
                $parentNode->relationAnchorPoint,
                $translatedNode->relationAnchorPoint,
                // TODO: position on insert is still missing
                null,
                $sourceNode->nodeName,
                $event->getContentStreamIdentifier(),
                $event->getVisibleDimensionSpacePoints()
            );
        });
    }

    /**
     * @param Event\NodesWereMoved $event
     * @throws \Exception
     */
    public function whenNodesWereMoved(Event\NodesWereMoved $event)
    {
        $this->transactional(function () use ($event) {
            foreach ($event->getNodeMoveMappings() as $moveNodeMapping) {
                $nodeToBeMoved = $this->projectionContentGraph->getNode($moveNodeMapping->getNodeIdentifier(), $event->getContentStreamIdentifier());
                $newSucceedingSibling = $moveNodeMapping->getNewSucceedingSiblingIdentifier()
                    ? $this->projectionContentGraph->getNode($moveNodeMapping->getNewSucceedingSiblingIdentifier(), $event->getContentStreamIdentifier())
                    : null;
                $inboundHierarchyRelations = $this->projectionContentGraph->findInboundHierarchyRelationsForNode($nodeToBeMoved->relationAnchorPoint, $event->getContentStreamIdentifier());
                if ($moveNodeMapping->getNewParentNodeIdentifier()) {
                    $newParentNode = $this->projectionContentGraph->getNode($moveNodeMapping->getNewParentNodeIdentifier(), $event->getContentStreamIdentifier());
                    foreach ($moveNodeMapping->getDimensionSpacePointSet()->getPoints() as $dimensionSpacePoint) {
                        $newPosition = $this->getRelationPosition(
                            $newParentNode->relationAnchorPoint,
                            null,
                            $newSucceedingSibling ? $newSucceedingSibling->relationAnchorPoint : null,
                            $event->getContentStreamIdentifier(),
                            $dimensionSpacePoint
                        );
                        $this->assignHierarchyRelationToNewParent($inboundHierarchyRelations[$dimensionSpacePoint->getHash()], $newParentNode->nodeIdentifier, $event->getContentStreamIdentifier(), $newPosition);
                    }
                } else {
                    foreach ($moveNodeMapping->getDimensionSpacePointSet()->getPoints() as $dimensionSpacePoint) {
                        $newPosition = $this->getRelationPosition(
                            null,
                            $nodeToBeMoved->relationAnchorPoint,
                            $newSucceedingSibling ? $newSucceedingSibling->relationAnchorPoint : null,
                            $event->getContentStreamIdentifier(),
                            $dimensionSpacePoint
                        );
                        $inboundHierarchyRelations[$dimensionSpacePoint->getHash()]->assignNewPosition($newPosition, $this->getDatabaseConnection());
                    }
                }
            }
        });
    }

    /**
     * @param Event\NodesWereRemovedFromAggregate $event
     * @throws \Exception
     */
    public function whenNodesWereRemovedFromAggregate(Event\NodesWereRemovedFromAggregate $event)
    {
        // the focus here is to be correct; that's why the method is not overly performant (for now at least). We might
        // lateron find tricks to improve performance
        $this->transactional(function () use ($event) {
            $inboundRelations = $this->projectionContentGraph->findInboundHierarchyRelationsForNodeAggregate($event->getContentStreamIdentifier(), $event->getNodeAggregateIdentifier(), $event->getDimensionSpacePointSet());
            foreach ($inboundRelations as $inboundRelation) {
                $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($inboundRelation);
            }
        });
    }

    /**
     * @param Event\NodeAggregateWasRemoved $event
     * @throws \Exception
     */
    public function whenNodeAggregateWasRemoved(Event\NodeAggregateWasRemoved $event)
    {
        // the focus here is to be correct; that's why the method is not overly performant (for now at least). We might
        // lateron find tricks to improve performance
        $this->transactional(function () use ($event) {
            $inboundRelations = $this->projectionContentGraph->findInboundHierarchyRelationsForNodeAggregate($event->getContentStreamIdentifier(), $event->getNodeAggregateIdentifier());
            foreach ($inboundRelations as $inboundRelation) {
                $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($inboundRelation);
            }
        });
    }

    protected function removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes(HierarchyRelation $inboundRelation)
    {
        $inboundRelation->removeFromDatabase($this->getDatabaseConnection());

        foreach ($this->projectionContentGraph->findOutboundHierarchyRelationsForNode($inboundRelation->childNodeAnchor, $inboundRelation->contentStreamIdentifier, new DimensionSpacePointSet([$inboundRelation->dimensionSpacePoint])) as $outboundRelation) {
            $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($outboundRelation);
        }

        // remove node itself if it does not have any incoming edges anymore
        $this->getDatabaseConnection()->executeUpdate('
            DELETE n FROM neos_contentgraph_node n
                LEFT JOIN
                    neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                WHERE
                    n.relationanchorpoint = :anchorPointForNode
                    AND h.contentstreamidentifier IS NULL
                ',
            [
                'anchorPointForNode' => (string)$inboundRelation->childNodeAnchor,
            ]
        );
    }

    /**
     * @param HierarchyRelation $hierarchyRelation
     * @param NodeIdentifier $newParentNodeIdentifier
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param int $position
     * @throws \Exception
     */
    protected function assignHierarchyRelationToNewParent(HierarchyRelation $hierarchyRelation, NodeIdentifier $newParentNodeIdentifier, ContentStreamIdentifier $contentStreamIdentifier, int $position)
    {
        $newParentNode = $this->projectionContentGraph->getNode($newParentNodeIdentifier, $contentStreamIdentifier);
        if (!$newParentNode) {
            throw new \Exception(sprintf('new parent %s for hierarchy relation in content stream %s not found.', $newParentNodeIdentifier, $contentStreamIdentifier), 1519768028);
        }
        $newHierarchyRelation = new HierarchyRelation(
            $newParentNode->relationAnchorPoint,
            $hierarchyRelation->childNodeAnchor,
            $hierarchyRelation->name,
            $hierarchyRelation->contentStreamIdentifier,
            $hierarchyRelation->dimensionSpacePoint,
            $hierarchyRelation->dimensionSpacePointHash,
            $position
        );
        $newHierarchyRelation->addToDatabase($this->getDatabaseConnection());
        $hierarchyRelation->removeFromDatabase($this->getDatabaseConnection());
    }

    /**
     * @param callable $operations
     * @throws \Exception
     */
    protected function transactional(callable $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
        $this->emitProjectionUpdated();
    }

    protected function updateNodeWithCopyOnWrite($event, callable $operations)
    {
        // TODO: do this copy on write on every modification op concerning nodes

        // TODO: does this always return a SINGLE anchor point??
        $anchorPointForNode = $this->projectionContentGraph->getAnchorPointForNodeAndContentStream($event->getNodeIdentifier(), $event->getContentStreamIdentifier());
        if ($anchorPointForNode === null) {
            // TODO Log error
            throw new \Exception(sprintf('anchor point for node identifier %s and stream %s not found', $event->getNodeIdentifier(), $event->getContentStreamIdentifier()), 1519681260000);
        }

        $contentStreamIdentifiers = $this->projectionContentGraph->getAllContentStreamIdentifiersAnchorPointIsContainedIn($anchorPointForNode);
        if (count($contentStreamIdentifiers) > 1) {
            // Copy on Write needed!
            // Copy on Write is a purely "Content Stream" related concept; thus we do not care about different DimensionSpacePoints here (but we copy all edges)

            // 1) fetch node, adjust properties, assign new Relation Anchor Point
            $copiedNode = $this->projectionContentGraph->getNodeByAnchorPoint($anchorPointForNode);
            $copiedNode->relationAnchorPoint = new NodeRelationAnchorPoint();
            $result = $operations($copiedNode);
            $copiedNode->addToDatabase($this->getDatabaseConnection());

            // 2) reconnect all edges belonging to this content stream to the new "copied node". IMPORTANT: We need to reconnect
            // BOTH the incoming and outgoing edges.
            $this->getDatabaseConnection()->executeUpdate('
                UPDATE neos_contentgraph_hierarchyrelation h
                    SET 
                        -- if our (copied) node is the child, we update h.childNodeAnchor
                        h.childnodeanchor = IF(h.childnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.childnodeanchor),
                        
                        -- if our (copied) node is the parent, we update h.parentNodeAnchor
                        h.parentnodeanchor = IF(h.parentnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.parentnodeanchor)
                    WHERE
                      :originalNodeAnchor IN (h.childnodeanchor, h.parentnodeanchor)
                      AND h.contentstreamidentifier = :contentStreamIdentifier',
                [
                    'newNodeAnchor' => (string)$copiedNode->relationAnchorPoint,
                    'originalNodeAnchor' => (string)$anchorPointForNode,
                    'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
                ]
            );
        } else {
            // No copy on write needed :)

            $node = $this->projectionContentGraph->getNodeByNodeIdentifierAndContentStream($event->getNodeIdentifier(), $event->getContentStreamIdentifier());
            if (!$node) {
                // TODO: ignore the ShowNode (if all other logic is correct)
                throw new \Exception("TODO NODE NOT FOUND");
            }

            $result = $operations($node);
            $node->updateToDatabase($this->getDatabaseConnection());
        }
        return $result;
    }

    /**
     * @return Connection
     */
    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }

}
