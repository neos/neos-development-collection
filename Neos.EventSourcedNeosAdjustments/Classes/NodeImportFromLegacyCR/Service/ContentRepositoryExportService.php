<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\NodeImportFromLegacyCR\Service;

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasCreated;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\DisableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeReferences;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeVariant;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateDisablingStrategy;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\EventSourcedNeosAdjustments\NodeImportFromLegacyCR\Service\Helpers\NodeAggregateIdentifierAndNodeTypeForLegacyImport;
use Neos\EventSourcing\Event\Decorator\EventWithIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\PsrSystemLoggerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Neos\Utility\TypeHandling;

/**
 * @Flow\Scope("singleton")
 */
class ContentRepositoryExportService
{

    /**
     * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
     * interface ...
     *
     * @var DoctrineObjectManager
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

    protected $contentStreamIdentifier;

    /**
     * @Flow\Inject
     * @var NodeCommandHandler
     */
    protected $nodeCommandHandler;

    /**
     * @Flow\Inject
     * @var PsrSystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * @Flow\Inject
     * @var EventStoreManager
     */
    protected $eventStoreManager;

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
     * key is the NodeAggregateIdentifier, value is the Dimension Space Point
     * @var DimensionSpacePoint
     */
    private $alreadyCreatedNodeAggregateIdentifiers;

    /**
     * @var CommandResult
     */
    private $commandResult;


    public function injectEntityManager(DoctrineObjectManager $entityManager)
    {
        if (!$entityManager instanceof DoctrineEntityManager) {
            throw new \RuntimeException('Invalid EntityManager configured');
        }
        $this->dbal = $entityManager->getConnection();
        $this->entityManager = $entityManager;
    }


    public function reset()
    {
        $this->dbal->executeQuery('
            SET foreign_key_checks = 0;
            
            TRUNCATE neos_eventsourcing_eventstore_events;
            TRUNCATE neos_eventsourcing_eventlistener_appliedeventslog;
            
            TRUNCATE neos_contentgraph_hierarchyrelation;
            TRUNCATE neos_contentgraph_node;
            TRUNCATE neos_contentgraph_referencerelation;
            TRUNCATE neos_contentgraph_restrictionedge;
            TRUNCATE neos_contentrepository_projection_change;
            TRUNCATE neos_contentrepository_projection_nodehiddenstate;
            TRUNCATE neos_contentrepository_projection_workspace_v1;
            TRUNCATE neos_neos_projection_domain_v1;
            TRUNCATE neos_neos_projection_site_v1;
            
            SET foreign_key_checks = 1;');
    }

    public function migrate()
    {
        $this->nodeAggregateCommandHandler->withoutAncestorNodeTypeConstraintChecks(function () {
            $this->migrateInternal();
        });
    }

    protected function migrateInternal()
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
    }

    protected function exportNodeData(NodeData $nodeData, &$nodeDatasToExportAtNextIteration)
    {
        $nodePath = NodePath::fromString($nodeData->getPath());

        $dimensionSpacePoint = DimensionSpacePoint::fromLegacyDimensionArray($nodeData->getDimensionValues());

        $parentNodeAggregateIdentifierAndNodeType = $this->findParentNodeAggregateIdentifierAndNodeType($nodeData->getParentPath(), $dimensionSpacePoint);
        if (!$parentNodeAggregateIdentifierAndNodeType) {
            // if parent node identifier not found, TRY LATER
            $nodeDatasToExportAtNextIteration[] = $nodeData;
            return;
        }


        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeData->getIdentifier());

        $this->exportNodeOrNodeAggregate(
            $nodeAggregateIdentifier,
            NodeTypeName::fromString($nodeData->getNodeType()->getName()),
            $dimensionSpacePoint,
            $parentNodeAggregateIdentifierAndNodeType,
            NodeName::fromString($nodeData->getName()),
            $this->processPropertyValues($nodeData),
            $this->processPropertyReferences($nodeData),
            $nodePath,
            $nodeData->isHidden()
        );
    }

    protected function exportNodeOrNodeAggregate(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePoint $originDimensionSpacePoint,
        NodeAggregateIdentifierAndNodeTypeForLegacyImport $parentNodeAggregateIdentifierAndNodeType,
        NodeName $nodeName,
        array $propertyValues,
        array $propertyReferences,
        NodePath $nodePath,
        bool $isHidden
    ) {
        echo $nodePath . "\n";

        try {
            $this->recordNodeAggregateIdentifierAndNodeType($nodePath, $originDimensionSpacePoint, $nodeAggregateIdentifier, $nodeTypeName);

            $isTethered = false;
            if ($this->nodeTypeManager->hasNodeType((string)$parentNodeAggregateIdentifierAndNodeType->getNodeTypeName())) {
                $nodeTypeOfParent = $this->nodeTypeManager->getNodeType((string)$parentNodeAggregateIdentifierAndNodeType->getNodeTypeName());
                $isTethered = $nodeTypeOfParent->hasAutoCreatedChildNode($nodeName);
            }

            if ($isTethered) {
                // we KNOW that tethered nodes already exist; so we just set its properties.
                if (!empty($propertyValues)) {
                    $this->nodeCommandHandler->handleSetNodeProperties(new SetNodeProperties(
                        $this->contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $originDimensionSpacePoint,
                        PropertyValues::fromArray($propertyValues)
                    ))->blockUntilProjectionsAreUpToDate();
                }
            } else {
                if (isset($this->alreadyCreatedNodeAggregateIdentifiers[(string)$nodeAggregateIdentifier])) {
                    $dimensionSpacePointOfAlreadyCreatedNode = $this->alreadyCreatedNodeAggregateIdentifiers[(string)$nodeAggregateIdentifier];

                    $this->nodeAggregateCommandHandler->handleCreateNodeVariant(new CreateNodeVariant(
                    // a Node of this NodeAggregate already exists; we create a Node variant
                        $this->contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $dimensionSpacePointOfAlreadyCreatedNode,
                        $originDimensionSpacePoint
                    ))->blockUntilProjectionsAreUpToDate();

                    $this->nodeCommandHandler->handleSetNodeProperties(new SetNodeProperties(
                        $this->contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $originDimensionSpacePoint,
                        PropertyValues::fromArray($propertyValues)
                    ))->blockUntilProjectionsAreUpToDate();
                } else {
                    $nodeAggregateIdentifiersByNodePaths = $this->findNodeAggregateIdentifiersForTetheredDescendantNodes($nodePath, $nodeTypeName);

                    $this->nodeAggregateCommandHandler->handleCreateNodeAggregateWithNode(new CreateNodeAggregateWithNode(
                        $this->contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $nodeTypeName,
                        $originDimensionSpacePoint,
                        UserIdentifier::forSystemUser(),
                        $parentNodeAggregateIdentifierAndNodeType->getNodeAggregateIdentifier(),
                        null,
                        $nodeName,
                        PropertyValues::fromArray($propertyValues),
                        $nodeAggregateIdentifiersByNodePaths
                    // TODO: tethered descendant IDs
                    ))->blockUntilProjectionsAreUpToDate();
                }
            }


            // publish reference edges
            foreach ($propertyReferences as $propertyName => $references) {
                $this->nodeCommandHandler->handleSetNodeReferences(new SetNodeReferences(
                    $this->contentStreamIdentifier,
                    $nodeAggregateIdentifier,
                    $originDimensionSpacePoint,
                    $references,
                    PropertyName::fromString($propertyName)
                ))->blockUntilProjectionsAreUpToDate();
            }

            if ($isHidden === true) {
                $this->nodeAggregateCommandHandler->handleDisableNodeAggregate(new DisableNodeAggregate(
                    $this->contentStreamIdentifier,
                    $nodeAggregateIdentifier,
                    $originDimensionSpacePoint,
                    NodeAggregateDisablingStrategy::gatherVirtualSpecializations()
                ));
            }

            $this->alreadyCreatedNodeAggregateIdentifiers[(string)$nodeAggregateIdentifier] = $originDimensionSpacePoint;
        } catch (\Exception $e) {
            $message = 'There was an error exporting the node ' . $nodeAggregateIdentifier . ' at path ' . $nodePath . ' in Dimension Space Point ' . $originDimensionSpacePoint . ':' . $e->getMessage();
            $this->systemLogger->warning($message, ['exception' => $e]);
            echo $message;
        }
    }

    protected function contentStreamName($suffix = null): StreamName
    {
        return StreamName::fromString('Neos.ContentRepository:ContentStream:' . $this->contentStreamIdentifier . ($suffix ? ':' . $suffix : ''));
    }

    private function processPropertyValues(NodeData $nodeData)
    {
        $properties = [];
        foreach ($nodeData->getProperties() as $propertyName => $propertyValue) {
            $type = $nodeData->getNodeType()->getPropertyType($propertyName);

            if ($type === 'reference' || $type === 'references') {
                // TODO: support other types than string
                continue;
            }
            $this->encodeObjectReference($propertyValue);
            $properties[$propertyName] = new PropertyValue($propertyValue, $type);
        }

        if ($nodeData->isHiddenInIndex()) {
            $properties['_hiddenInIndex'] = new PropertyValue($nodeData->isHiddenInIndex(), 'boolean');
        }

        return $properties;
    }

    protected function encodeObjectReference(&$value)
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $this->encodeObjectReference($item);
            }
        }

        if (!is_object($value)) {
            return;
        }

        $propertyClassName = TypeHandling::getTypeForValue($value);

        if ($value instanceof \DateTimeInterface) {
            $value = [
                'date' => $value->format('Y-m-d H:i:s.u'),
                'timezone' => $value->format('e'),
                'dateFormat' => 'Y-m-d H:i:s.u'
            ];
        } else {
            $value = [
                '__flow_object_type' => $propertyClassName,
                '__identifier' => $this->persistenceManager->getIdentifierByObject($value)
            ];
        }
    }

    private function processPropertyReferences(NodeData $nodeData)
    {
        $references = [];

        foreach ($nodeData->getProperties() as $propertyName => $propertyValue) {
            try {
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
                $message = 'There was an error exporting the reference ' . $propertyName . ' at path ' . $nodeData->getContextPath() . ':' . $e->getMessage();
                $this->systemLogger->warning($message, ['exception' => $e]);
            }
        }
        return $references;
    }

    private function findParentNodeAggregateIdentifierAndNodeType($parentPath, DimensionSpacePoint $dimensionSpacePoint): ?NodeAggregateIdentifierAndNodeTypeForLegacyImport
    {
        if ($parentPath === '/sites') {
            return $this->nodeAggregateIdentifierForSitesNode;
        }

        while ($dimensionSpacePoint !== null) {
            $key = $parentPath . '__' . $dimensionSpacePoint->getHash();
            if (isset($this->nodeAggregateIdentifiers[$key])) {
                return $this->nodeAggregateIdentifiers[$key];
            }

            $dimensionSpacePoint = $this->interDimensionalFallbackGraph->getPrimaryGeneralization($dimensionSpacePoint);
        }

        return null;
    }

    private function recordNodeAggregateIdentifierAndNodeType(NodePath $nodePath, DimensionSpacePoint $dimensionSpacePoint, NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeName $nodeTypeName)
    {
        $key = $nodePath . '__' . $dimensionSpacePoint->getHash();
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
    private function findNodeAggregateIdentifiersForTetheredDescendantNodes(NodePath $nodePath, NodeTypeName $nodeTypeName): NodeAggregateIdentifiersByNodePaths
    {
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

                $nestedNodeAggregateIdentifiersByNodePath = $this->findNodeAggregateIdentifiersForTetheredDescendantNodes($nodePathOfTetheredNode, NodeTypeName::fromString($childNodeType->getName()));
                foreach ($nestedNodeAggregateIdentifiersByNodePath->getNodeAggregateIdentifiers() as $nodePathString => $nodeAggregateIdentifier) {
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
            new WorkspaceName('live'),
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
            NodeAggregateClassification::root(),
            UserIdentifier::forSystemUser()
        );
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($this->contentStreamIdentifier)->getEventStreamName();
        $this->commitEvent($streamName, $event);
    }

    private function commitEvent(StreamName $streamName, DomainEventInterface $event): void
    {
        $event = EventWithIdentifier::create($event);
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $publishedEvents = DomainEvents::withSingleEvent($event);
        $eventStore->commit($streamName, $publishedEvents);

        CommandResult::fromPublishedEvents(
            $publishedEvents
        )->blockUntilProjectionsAreUpToDate();
    }
}
