<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Service;

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\Feature\NodeDisabling\Command\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Feature\Common\NodeAggregateIdentifiersByNodePaths;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
/** @codingStandardsIgnoreStart */
use Neos\ContentRepository\LegacyNodeMigration\Service\Helpers\NodeAggregateIdentifierAndNodeTypeForLegacyImport;
/** @codingStandardsIgnoreEnd */
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class ContentRepositoryExportService
{
    /**
     * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
     * interface ...
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var InterDimensionalVariationGraph
     */
    protected $interDimensionalFallbackGraph;

    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @Flow\Inject
     * @var ContentDimensionZookeeper
     */
    protected $contentDimensionZookeeper;

    /**
     * @var NodeAggregateIdentifierAndNodeTypeForLegacyImport
     */
    private $nodeAggregateIdentifierForSitesNode;

    /**
     * @var NodeAggregateIdentifierAndNodeTypeForLegacyImport[]
     */
    private $nodeAggregateIdentifiers;

    /**
     * key is the NodeAggregateIdentifier, value is the Origin Dimension Space Point
     * @var array<string,OriginDimensionSpacePoint>
     */
    private array $alreadyCreatedNodeAggregateIdentifiers;

    /**
     * it can happen that the reference target has not been imported yet
     * - that's why we collect all {@link SetNodeReferences} commands here in this array
     * and run them **after** the general migration has run.
     *
     * @var SetNodeReferences[]
     */
    private $setNodeReferenceCommands = [];

    /**
     * @Flow\Inject
     * @var RuntimeBlocker
     */
    protected $runtimeBlocker;

    /**
     * @var CommandResult
     */
    private $commandResult;

    public function __construct(EventStore $eventStore, NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->eventStore = $eventStore;
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    public function injectEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->dbal = $entityManager->getConnection();
        $this->entityManager = $entityManager;
    }

    public function reset(): void
    {
        try {
            $this->dbal->executeUpdate('SET foreign_key_checks = 0');
            $this->dbal->executeUpdate('TRUNCATE neos_contentrepository_projection_workspace_v1');
            $this->dbal->executeUpdate('TRUNCATE neos_contentrepository_events');
            $this->dbal->executeUpdate('UPDATE neos_eventsourcing_eventlistener_appliedeventslog
                SET highestappliedsequencenumber=-1');
            $this->dbal->executeUpdate('TRUNCATE neos_contentgraph_hierarchyrelation');
            $this->dbal->executeUpdate('TRUNCATE neos_contentgraph_node');
            $this->dbal->executeUpdate('TRUNCATE neos_contentgraph_referencerelation');
            $this->dbal->executeUpdate('TRUNCATE neos_contentgraph_restrictionrelation');
            $this->dbal->executeUpdate('TRUNCATE neos_contentrepository_projection_change');
            $this->dbal->executeUpdate('TRUNCATE neos_contentrepository_projection_nodehiddenstate');
            $this->dbal->executeUpdate('TRUNCATE neos_neos_projection_domain_v1');
            $this->dbal->executeUpdate('TRUNCATE neos_neos_projection_site_v1');
        } finally {
            $this->dbal->executeUpdate('SET foreign_key_checks = 1');
        }
    }

    public function migrate(): void
    {
        $this->nodeAggregateCommandHandler->withoutAncestorNodeTypeConstraintChecks(function () {
            $this->migrateInternal();
        });
    }

    protected function migrateInternal(): void
    {
        $this->nodeAggregateIdentifiers = [];
        $this->alreadyCreatedNodeAggregateIdentifiers = [];

        $this->contentStreamIdentifier = ContentStreamIdentifier::create();
        $this->nodeAggregateIdentifierForSitesNode = new NodeAggregateIdentifierAndNodeTypeForLegacyImport(
            NodeAggregateIdentifier::create(),
            NodeTypeName::fromString('Neos.Neos:Sites')
        );

        $this->commandResult = CommandResult::createEmpty();

        $streamName = $this->contentStreamName();
        $event = new ContentStreamWasCreated(
            $this->contentStreamIdentifier,
            UserIdentifier::forSystemUser()
        );
        $this->commitEvent($streamName, $event);

        $this->createRootWorkspace();
        $this->createRootNode();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('n')
            ->from(NodeData::class, 'n')
            ->where('n.workspace = :workspace')
            ->andWhere('n.movedTo IS NULL OR n.removed = :removed')
            ->andWhere('n.path NOT IN(\'/sites\', \'/\')')
            ->orderBy('n.parentPath', 'ASC')
            ->addOrderBy('n.index', 'ASC')
            ->setParameter('workspace', 'live')
            ->setParameter('removed', false, \PDO::PARAM_BOOL);

        $i = 0;
        $batchSize = 100;
        $nodeDatasToExportAtNextIteration = [];

        foreach ($queryBuilder->getQuery()->iterate() as $nodeDataRows) {
            $i++;
            $nodeData = $nodeDataRows[0];
            $this->exportNodeData($nodeData, $nodeDatasToExportAtNextIteration);

            if (($i % $batchSize) === 0) {
                $this->persistenceManager->clearState();
            }
        }
        var_dump("NODE DATAS IN NEXT ITER: " . count($nodeDatasToExportAtNextIteration));


        $nodeDatas = $nodeDatasToExportAtNextIteration;
        $nodeDatasToExportAtNextIteration = [];
        // TODO: correct sorting with respect to iteration!!
        foreach ($nodeDatas as $nodeData) {
            $this->exportNodeData($nodeData, $nodeDatasToExportAtNextIteration);
        }
        var_dump("NODE DATAS IN NEXT ITER: " . count($nodeDatasToExportAtNextIteration));

        $this->commandResult->blockUntilProjectionsAreUpToDate();

        // Set References, now when the full import is done.
        foreach ($this->setNodeReferenceCommands as $setNodeReferenceCommand) {
            $this->commandResult = $this->nodeAggregateCommandHandler->handleSetNodeReferences(
                $setNodeReferenceCommand
            );
        }
        $this->setNodeReferenceCommands = [];

        $this->commandResult->blockUntilProjectionsAreUpToDate();
    }

    /**
     * @param array<int,NodeData> &$nodeDatasToExportAtNextIteration
     */
    protected function exportNodeData(NodeData $nodeData, array &$nodeDatasToExportAtNextIteration): void
    {
        $nodePath = NodePath::fromString(strtolower($nodeData->getPath()));

        $originDimensionSpacePoint = OriginDimensionSpacePoint::fromLegacyDimensionArray(
            $nodeData->getDimensionValues()
        );

        $parentNodeAggregateIdentifierAndNodeType = $this->findParentNodeAggregateIdentifierAndNodeType(
            $nodeData->getParentPath(),
            $originDimensionSpacePoint->toDimensionSpacePoint()
        );
        if (!$parentNodeAggregateIdentifierAndNodeType) {
            // if parent node identifier not found, TRY LATER
            $nodeDatasToExportAtNextIteration[] = $nodeData;
            return;
        }


        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeData->getIdentifier());

        $this->exportNodeOrNodeAggregate(
            $nodeAggregateIdentifier,
            NodeTypeName::fromString($nodeData->getNodeType()->getName()),
            $originDimensionSpacePoint,
            $parentNodeAggregateIdentifierAndNodeType,
            NodeName::fromString($nodeData->getName()),
            $this->processPropertyValues($nodeData),
            $this->processPropertyReferences($nodeData),
            $nodePath,
            $nodeData->isHidden()
        );
    }

    /**
     * @param array<string,mixed> $propertyValues
     * @param array<string,array<int,NodeAggregateIdentifier>> $propertyReferences
     */
    protected function exportNodeOrNodeAggregate(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeAggregateIdentifierAndNodeTypeForLegacyImport $parentNodeAggregateIdentifierAndNodeType,
        NodeName $nodeName,
        array $propertyValues,
        array $propertyReferences,
        NodePath $nodePath,
        bool $isHidden
    ): void {
        echo $nodePath . ' (' . $nodeAggregateIdentifier . ")\n";

        try {
            $this->recordNodeAggregateIdentifierAndNodeType(
                $nodePath,
                $originDimensionSpacePoint->toDimensionSpacePoint(),
                $nodeAggregateIdentifier,
                $nodeTypeName
            );

            $isTethered = false;
            if ($this->nodeTypeManager->hasNodeType(
                (string)$parentNodeAggregateIdentifierAndNodeType->getNodeTypeName()
            )) {
                $nodeTypeOfParent = $this->nodeTypeManager->getNodeType(
                    (string)$parentNodeAggregateIdentifierAndNodeType->getNodeTypeName()
                );
                $isTethered = $nodeTypeOfParent->hasAutoCreatedChildNode($nodeName);
            }

            if ($isTethered) {
                // we KNOW that tethered nodes already exist; so we just set its properties.
                if (!empty($propertyValues)) {
                    $this->nodeAggregateCommandHandler->handleSetNodeProperties(new SetNodeProperties(
                        $this->contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $originDimensionSpacePoint,
                        PropertyValuesToWrite::fromArray($propertyValues),
                        UserIdentifier::forSystemUser()
                    ))->blockUntilProjectionsAreUpToDate();
                }
            } else {
                if (isset($this->alreadyCreatedNodeAggregateIdentifiers[(string)$nodeAggregateIdentifier])) {
                    $dimensionSpacePointOfAlreadyCreatedNode
                        = $this->alreadyCreatedNodeAggregateIdentifiers[(string)$nodeAggregateIdentifier];

                    $this->nodeAggregateCommandHandler->handleCreateNodeVariant(new CreateNodeVariant(
                    // a Node of this NodeAggregate already exists; we create a Node variant
                        $this->contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $dimensionSpacePointOfAlreadyCreatedNode,
                        $originDimensionSpacePoint,
                        UserIdentifier::forSystemUser()
                    ))->blockUntilProjectionsAreUpToDate();

                    $this->nodeAggregateCommandHandler->handleSetNodeProperties(new SetNodeProperties(
                        $this->contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $originDimensionSpacePoint,
                        PropertyValuesToWrite::fromArray($propertyValues),
                        UserIdentifier::forSystemUser()
                    ))->blockUntilProjectionsAreUpToDate();
                } else {
                    $nodeAggregateIdentifiersByNodePaths
                        = $this->findNodeAggregateIdentifiersForTetheredDescendantNodes(
                            $nodePath,
                            $nodeTypeName
                        );
                    $this->nodeAggregateCommandHandler->handleCreateNodeAggregateWithNode(
                        new CreateNodeAggregateWithNode(
                            $this->contentStreamIdentifier,
                            $nodeAggregateIdentifier,
                            $nodeTypeName,
                            $originDimensionSpacePoint,
                            UserIdentifier::forSystemUser(),
                            $parentNodeAggregateIdentifierAndNodeType->getNodeAggregateIdentifier(),
                            null,
                            $nodeName,
                            PropertyValuesToWrite::fromArray($propertyValues),
                            $nodeAggregateIdentifiersByNodePaths
                        )
                    )->blockUntilProjectionsAreUpToDate();
                }
            }

            // publish reference edges
            foreach ($propertyReferences as $propertyName => $references) {
                $this->setNodeReferenceCommands[] = new SetNodeReferences(
                    $this->contentStreamIdentifier,
                    $nodeAggregateIdentifier,
                    $originDimensionSpacePoint,
                    NodeAggregateIdentifiers::fromArray($references),
                    PropertyName::fromString($propertyName),
                    UserIdentifier::forSystemUser()
                );
            }

            if ($isHidden === true) {
                $this->nodeAggregateCommandHandler->handleDisableNodeAggregate(
                    new DisableNodeAggregate(
                        $this->contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $originDimensionSpacePoint->toDimensionSpacePoint(),
                        NodeVariantSelectionStrategy::STRATEGY_VIRTUAL_SPECIALIZATIONS,
                        UserIdentifier::forSystemUser()
                    )
                )->blockUntilProjectionsAreUpToDate();
            }

            $this->alreadyCreatedNodeAggregateIdentifiers[(string)$nodeAggregateIdentifier]
                = $originDimensionSpacePoint;
        } catch (\Exception $e) {
            throw $e;
            /*
            $message = 'There was an error exporting the node ' . $nodeAggregateIdentifier . ' at path ' . $nodePath
                . ' in Dimension Space Point ' . $originDimensionSpacePoint . ':' . $e->getMessage();
            $this->systemLogger->warning($message, ['exception' => $e]);
            echo $message;*/
        }
    }

    protected function contentStreamName(string $suffix = null): StreamName
    {
        return StreamName::fromString(
            'Neos.ContentRepository:ContentStream:'
                . $this->contentStreamIdentifier . ($suffix ? ':' . $suffix : '')
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function processPropertyValues(NodeData $nodeData): array
    {
        $properties = [];
        foreach ($nodeData->getProperties() as $propertyName => $propertyValue) {
            // WORKAROUND: $nodeType->getPropertyType() is missing the "initialize" call,
            // so we need to trigger another method beforehand.
            $nodeData->getNodeType()->getFullConfiguration();
            $type = $nodeData->getNodeType()->getPropertyType($propertyName);

            if ($type === 'reference' || $type === 'references') {
                // TODO: support other types than string
                continue;
            }

            // In the old `NodeInterface`, we call the property mapper to convert the returned properties from NodeData;
            // so we need to do the same here.
            $properties[$propertyName] = $this->propertyMapper->convert($propertyValue, $type);
        }

        if ($nodeData->isHiddenInIndex()) {
            $properties['_hiddenInIndex'] = $nodeData->isHiddenInIndex();
        }

        return $properties;
    }

    /**
     * @return array<string,array<int,NodeAggregateIdentifier>>
     */
    private function processPropertyReferences(NodeData $nodeData): array
    {
        $references = [];

        foreach ($nodeData->getProperties() as $propertyName => $propertyValue) {
            try {
                // WORKAROUND: $nodeType->getPropertyType() is missing the "initialize" call,
                // so we need to trigger another method beforehand.
                $nodeData->getNodeType()->getFullConfiguration();
                $type = $nodeData->getNodeType()->getPropertyType($propertyName);
                if ($type === 'reference' && !empty($propertyValue)) {
                    $references[$propertyName] = [NodeAggregateIdentifier::fromString($propertyValue)];
                }
                if ($type === 'references' && is_array($propertyValue)) {
                    $references[$propertyName] = array_map(function ($identifier) {
                        return NodeAggregateIdentifier::fromString($identifier);
                    }, $propertyValue);
                }
            } catch (\Exception $e) {
                $message = 'There was an error exporting the reference ' . $propertyName
                    . ' at path ' . $nodeData->getContextPath() . ':' . $e->getMessage();
                $this->systemLogger->warning($message, ['exception' => $e]);
            }
        }
        return $references;
    }

    private function findParentNodeAggregateIdentifierAndNodeType(
        string $parentPath,
        DimensionSpacePoint $dimensionSpacePoint
    ): ?NodeAggregateIdentifierAndNodeTypeForLegacyImport {
        if ($parentPath === '/sites') {
            return $this->nodeAggregateIdentifierForSitesNode;
        }

        while ($dimensionSpacePoint !== null) {
            $key = strtolower($parentPath) . '__' . $dimensionSpacePoint->hash;
            if (isset($this->nodeAggregateIdentifiers[$key])) {
                return $this->nodeAggregateIdentifiers[$key];
            }

            $dimensionSpacePoint = $this->interDimensionalFallbackGraph->getPrimaryGeneralization($dimensionSpacePoint);
        }

        return null;
    }

    private function recordNodeAggregateIdentifierAndNodeType(
        NodePath $nodePath,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName
    ): void {
        $key = strtolower($nodePath->jsonSerialize()) . '__' . $dimensionSpacePoint->hash;
        if (isset($this->nodeAggregateIdentifiers[$key])) {
            throw new \RuntimeException('TODO: node identifier ' . $key . 'already known!!!');
        }
        $this->nodeAggregateIdentifiers[$key] = new NodeAggregateIdentifierAndNodeTypeForLegacyImport(
            $nodeAggregateIdentifier,
            $nodeTypeName
        );
    }


    /**
     * @param NodePath $nodePath
     * @param NodeTypeName $nodeTypeName
     * @return NodeAggregateIdentifiersByNodePaths
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    private function findNodeAggregateIdentifiersForTetheredDescendantNodes(
        NodePath $nodePath,
        NodeTypeName $nodeTypeName
    ): NodeAggregateIdentifiersByNodePaths {
        $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeTypeName);

        $nodeAggregateIdentifiersByNodePath = [];
        foreach ($nodeType->getAutoCreatedChildNodes() as $nodeName => $childNodeType) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $this->entityManager->createQueryBuilder();
            $nodePathOfTetheredNode = $nodePath->appendPathSegment(NodeName::fromString($nodeName));

            $query = $queryBuilder->select('n')
                ->from(NodeData::class, 'n')
                ->where('n.workspace = :workspace')
                ->andWhere('n.movedTo IS NULL OR n.removed = :removed')
                ->andWhere('n.path = :path')
                ->setParameter('workspace', 'live')
                ->setParameter('removed', false, \PDO::PARAM_BOOL)
                ->setParameter('path', $nodePathOfTetheredNode)
                ->setMaxResults(1);

            // all tethered nodes have the same Node Identifier; so we can just go for the first one.
            $tetheredChildren = $query->getQuery()->execute();
            if (count($tetheredChildren) > 0) {
                // if we find a tethered child, we step one level down.
                $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($tetheredChildren[0]->getIdentifier());
                $nodeAggregateIdentifiersByNodePath[$nodeName] = $nodeAggregateIdentifier;

                $nestedNodeAggregateIdentifiersByNodePath
                    = $this->findNodeAggregateIdentifiersForTetheredDescendantNodes(
                        $nodePathOfTetheredNode,
                        NodeTypeName::fromString($childNodeType->getName())
                    );
                $nodeAggregateIdentifiers = $nestedNodeAggregateIdentifiersByNodePath->getNodeAggregateIdentifiers();
                foreach ($nodeAggregateIdentifiers as $nodePathString => $nodeAggregateIdentifier) {
                    $nodeAggregateIdentifiersByNodePath[$nodeName . '/' . $nodePathString] = $nodeAggregateIdentifier;
                }
            }
        }

        return NodeAggregateIdentifiersByNodePaths::fromArray($nodeAggregateIdentifiersByNodePath);
    }


    private function createRootWorkspace(): void
    {
        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:live');
        $event = new RootWorkspaceWasCreated(
            WorkspaceName::forLive(),
            new WorkspaceTitle('Live'),
            new WorkspaceDescription(''),
            UserIdentifier::forSystemUser(),
            $this->contentStreamIdentifier
        );
        $this->commitEvent($streamName, $event);
    }

    private function createRootNode(): void
    {
        $dimensionSpacePointSet = $this->contentDimensionZookeeper->getAllowedDimensionSubspace();
        $event = new RootNodeAggregateWithNodeWasCreated(
            $this->contentStreamIdentifier,
            $this->nodeAggregateIdentifierForSitesNode->getNodeAggregateIdentifier(),
            NodeTypeName::fromString('Neos.Neos:Sites'),
            $dimensionSpacePointSet,
            NodeAggregateClassification::CLASSIFICATION_ROOT,
            UserIdentifier::forSystemUser()
        );
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($this->contentStreamIdentifier)
            ->getEventStreamName();
        $this->commitEvent($streamName, $event);
    }

    private function commitEvent(StreamName $streamName, DomainEventInterface $event): void
    {
        $event = DecoratedEvent::addIdentifier($event, Uuid::uuid4()->toString());
        $publishedEvents = DomainEvents::withSingleEvent($event);
        $this->eventStore->commit($streamName, $publishedEvents);

        $commandResult = CommandResult::fromPublishedEvents($publishedEvents, $this->runtimeBlocker);
        $commandResult->blockUntilProjectionsAreUpToDate();
    }
}
