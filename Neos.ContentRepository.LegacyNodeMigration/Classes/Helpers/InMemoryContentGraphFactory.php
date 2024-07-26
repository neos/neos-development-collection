<?php

declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

/*
 * This file is part of the Neos.ContentRepository.InMemoryGraph package.
 */

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentRepository\BehavioralTests\PhpstanRules\Utility\ClassClassification;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimension;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Property\PropertyMapper;

/**
 * The service for building up the content graph
 */
final class InMemoryContentGraphFactory
{
    /**
     * @var DimensionSpace\InterDimensionalVariationGraph
     */
    protected $variationGraph;

    /**
     * @var DimensionSpace\ContentDimensionZookeeper
     */
    protected $contentDimensionZookeeper;

    /**
     * @var ContentRepository\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var ContentRepository\Repository\WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var array|ContentRepository\Model\Workspace[]
     */
    protected $indexedWorkspaces;

    /**
     * @var ContentDimensionIdentifier
     */
    protected $workspaceDimensionIdentifier;

    /**
     * @var array|string[]
     */
    protected $systemNodeIdentifiers;

    /**
     * @var DimensionSpacePointFactory
     */
    protected $dimensionSpacePointFactory;

    private readonly ContentRepository $contentRepository;

    public function __construct(
        private readonly ContentDimensionSourceInterface $contentDimensionSource,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly PropertyConverter $propertyConverter,
        DimensionSpace\InterDimensionalVariationGraph $variationGraph,
        DimensionSpace\ContentDimensionZookeeper $contentDimensionZookeeper,
        ContentRepository\Repository\WorkspaceRepository $workspaceRepository,
        ContentRepository\Repository\NodeDataRepository $nodeDataRepository,
    ) {
        $this->variationGraph = $variationGraph;
        $this->contentDimensionZookeeper = $contentDimensionZookeeper;
        $this->workspaceRepository = $workspaceRepository;
        $this->nodeDataRepository = $nodeDataRepository;
        $this->workspaceDimensionIdentifier = new ContentDimensionIdentifier(LegacyConfigurationAndWorkspaceBasedContentDimensionSource::WORKSPACE_DIMENSION_IDENTIFIER);
    }

    public function getContentGraph(ConsoleOutput $output = null): ContentGraph
    {
        $nodeDataRecords = $this->fetchNodeDataRecords();

        return $this->createContentGraphForNodeDataRecords($nodeDataRecords, $output);
    }

    /**
     * @param \Traversable<int,array<string,mixed>> $nodeDataRecords ordered by path
     */
    public function createContentGraphForNodeDataRecords(
        \Traversable $nodeDataRecords,
    ): InMemoryContentGraph {
        $contentRepositoryId = ContentRepositoryId::fromString('default');
        $workspaceName = WorkspaceName::forLive();
        $contentStreamId = ContentStreamId::create();

        $subgraphs = $this->getSubgraphs();
        $this->variationGraph->getRootGeneralizations();

        $nodes = [];
        $nodesByAggregateId = [];
        $nodesByPathAndOriginDSP = [];
        foreach ($nodeDataRecords as $nodeDataRecord) {
            /** @var array $nodeDataRecord */
            $dimensionSpacePoint = DimensionSpacePointFactory::tryCreateFromLegacyArray(
                \json_decode($nodeDataRecord['dimensionvalues'], true, flags: JSON_THROW_ON_ERROR),
                $this->contentDimensionSource,
            );
            if (!$dimensionSpacePoint) {
                continue;
            }

            $nodeTypeName = $nodeDataRecord['path'] === '/sites'
                ? NodeTypeName::fromString('Neos.Neos:Sites')
                : NodeTypeName::fromString($nodeDataRecord['nodetype']);

            $nodeType = $this->nodeTypeManager->getNodeType($nodeDataRecord['nodetype']);
            if (!$nodeType) {
                continue;
            }

            $nodeNames = NodePath::fromString($nodeDataRecord['path'])->getParts();
            $nodeName = array_pop($nodeNames);

            if ($nodeDataRecord['parentpath'] === '/') {
                $classification = NodeAggregateClassification::CLASSIFICATION_ROOT;
            } else {
                $parentNodeType = $this->nodeTypeManager->getNodeType(
                    $nodesByPathAndOriginDSP[$nodeDataRecord['parentpath']][$dimensionSpacePoint->hash]?->nodeTypeName ?: 'unstructured'
                );
                $classification = $parentNodeType?->tetheredNodeTypeDefinitions->get($nodeName)
                    ? NodeAggregateClassification::CLASSIFICATION_TETHERED
                    : NodeAggregateClassification::CLASSIFICATION_REGULAR;
            }
            $propertiesAndReferences = $this->extractPropertyValuesAndReferences($nodeDataRecord, $nodeType);
            $tags = $this->isNodeHidden($nodeDataRecord)
                ? SubtreeTags::fromArray([SubtreeTag::disabled()])
                : SubtreeTags::createEmpty();
            $node = Node::create(
                $contentRepositoryId,
                WorkspaceName::forLive(),
                $dimensionSpacePoint,
                NodeAggregateId::fromString($nodeDataRecord['identifier']),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint),
                $classification,
                $nodeTypeName,
                new PropertyCollection(
                    $propertiesAndReferences->serializedPropertyValues,
                    $this->propertyConverter,
                ),
                $nodeName,
                NodeTags::create(
                    $tags,
                    // for now, we only set explicitly set ones, inherited ones are added later
                    SubtreeTags::createEmpty(),
                ),
                Timestamps::create(
                    new \DateTimeImmutable($nodeDataRecord['lastpublicationdatetime'] ?: $nodeDataRecord['creationdatetime']),
                    new \DateTimeImmutable($nodeDataRecord['creationdatetime']),
                    new \DateTimeImmutable($nodeDataRecord['lastpublicationdatetime'] ?: $nodeDataRecord['lastmodificationdatetime']),
                    new \DateTimeImmutable($nodeDataRecord['lastmodificationdatetime']),
                ),
                VisibilityConstraints::withoutRestrictions(),
                $contentStreamId,
            );
            $nodesByPathAndOriginDSP[$nodeDataRecord['path']][$dimensionSpacePoint->hash] = $node;
            $nodesByAggregateId[$node->aggregateId->value][] = $node;
        }

        $aggregates = $this->groupNodesToAggregates($contentRepositoryId, $workspaceName, $nodesByAggregateId);
        if ($output) {
            $output->outputLine('Initialized node aggregates after ' . (microtime(true) - $start));
        }
        $assignments = $this->determineNodeAssignments($aggregates, $subgraphs, $output);
        if ($output) {
            $output->outputLine('Initialized node assignments after ' . (microtime(true) - $start));
        }
        $result = new ContentGraph($subgraphs, $nodes, $aggregates, $assignments, $output);
        if ($output) {
            $output->outputLine('Initialized graph after ' . (microtime(true) - $start));
            $output->outputLine('Memory used: %4.2fMB', [memory_get_peak_usage(true) / 1048576]);
        }
        return $result;
    }

    /**
     * @return array|ContentSubgraph[]
     */
    protected function getSubgraphs(): array
    {
        $subgraphs = [];
        $allowedDimensionSubspace = $this->contentDimensionZookeeper->getAllowedDimensionSubspace();

        foreach ($allowedDimensionSubspace as $dimensionSpacePoint) {
            $subgraph = new ContentSubgraph($this->getWorkspaceForDimensionSpacePoint($dimensionSpacePoint), $dimensionSpacePoint);
            $subgraphs[(string)$subgraph] = $subgraph;
        }

        return $subgraphs;
    }

    /**
     * @return QueryResultInterface|ContentRepository\Model\NodeData[]
     */
    protected function fetchNodeDataRecords(): QueryResultInterface
    {
        $query = $this->nodeDataRepository->createQuery();
        $query->setOrderings([
            'path' => 'ASC',
            'workspace' => 'ASC'
        ]);

        return $query->execute();
    }

    /**
     * @param array<string,array<int,Node>> $nodesByAggregateId
     * @return array<int,NodeAggregate>
     */
    protected function groupNodesToAggregates(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        array $nodesByAggregateId
    ): array {
        $aggregates = [];

        foreach ($nodesByAggregateId as $nodeAggregateId => $nodes) {
            if (empty($nodes)) {
                continue;
            }
            $classification = null;
            $nodeTypeName = null;
            $nodeName = null;
            $occupiedDimensionSpacePoints = [];
            $nodesByOccupiedDimensionSpacePoint = [];
            foreach ($nodes as $node) {
                if (!$classification) {
                    $classification = $node->classification;
                } elseif ($node->classification !== $classification) {
                    throw new \DomainException('Node aggregate ' . $nodeAggregateId . ' is inconsistently classified.', 1721426622);
                }
                if (!$nodeTypeName) {
                    $nodeTypeName = $node->nodeTypeName;
                } elseif (!$node->nodeTypeName->equals($nodeTypeName)) {
                    throw new \DomainException('Node aggregate ' . $nodeAggregateId . ' is inconsistently typed.', 1721426842);
                }
                if (!$nodeName) {
                    $nodeName = $node->name;
                } elseif (!$node->name->equals($nodeName)) {
                    throw new \DomainException('Node aggregate ' . $nodeAggregateId . ' is inconsistently named.', 1721426917);
                }

                $occupiedDimensionSpacePoints[] = $node->originDimensionSpacePoint;
                $nodesByOccupiedDimensionSpacePoint[$node->originDimensionSpacePoint->hash] = $node;
            }
            $aggregates[$nodeAggregateId] = NodeAggregate::create(
                $contentRepositoryId,
                $workspaceName,
                NodeAggregateId::fromString($nodeAggregateId),
                $classification,
                $nodeTypeName,
                $nodeName,
                OriginDimensionSpacePointSet::fromArray($occupiedDimensionSpacePoints),
                $nodesByOccupiedDimensionSpacePoint,
                ''
        }
        if ($output) {
            $output->progressFinish();
            $output->outputLine();
        }

        return $aggregates;
    }

    /**
     * @param array|NodeAggregate[] $aggregates
     * @param array|ContentSubgraph[] $subgraphs
     * @param ConsoleOutput|null $output
     * @return NodeAssignmentRegistry
     */
    protected function determineNodeAssignments(array $aggregates, array $subgraphs, ConsoleOutput $output = null): NodeAssignmentRegistry
    {
        $nodeAssignmentRegistry = new NodeAssignmentRegistry();

        if ($output) {
            $output->progressStart(count($aggregates));
        }
        foreach ($aggregates as $aggregateIdentifier => $aggregate) {
            foreach ($subgraphs as $subgraph) {
                $node = $this->findBestSuitedNodeForSubgraph(
                    $subgraph->getWorkspace(),
                    $subgraph->getDimensionSpacePoint(),
                    $aggregate
                );
                if ($node) {
                    $path = $node->getPath();
                    $nodeAssignmentRegistry->registerNodeByPathAndSubgraphIdentifier(
                        $path,
                        $subgraph->getIdentifier(),
                        $node
                    );
                    $nodeAssignmentRegistry->registerSubgraphIdentifierByPathAndNodeIdentifier(
                        $path,
                        $node->getCacheEntryIdentifier(),
                        $subgraph->getIdentifier()
                    );
                }
            }
            if ($output) {
                $output->progressAdvance();
            }
        }
        if ($output) {
            $output->progressFinish();
        }

        return $nodeAssignmentRegistry;
    }

    protected function findBestSuitedNodeForSubgraph(
        ContentRepository\Model\Workspace $workspace,
        DimensionSpace\DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregate $nodeAggregate
    ): ?Node {
        if ($nodeAggregate->isRoot()) {
            $nodes = $nodeAggregate->getNodesByWorkspace($workspace);
            return reset($nodes);
        } elseif (isset($this->systemNodeIdentifiers[(string)$nodeAggregate->getIdentifier()])) {
            $nodes = $nodeAggregate->getNodes();
            return reset($nodes);
        }

        $node = $nodeAggregate->getNodeByDimensionSpacePoint($dimensionSpacePoint);
        if ($node) {
            return $node;
        } else {
            foreach ($this->variationGraph->getWeightedGeneralizations($dimensionSpacePoint) as $generalization) {
                $node = $nodeAggregate->getNodeByDimensionSpacePoint($generalization);
                if ($node) {
                    return $node;
                }
            }
        }

        return null;
    }

    protected function getWorkspaceForDimensionSpacePoint(DimensionSpace\DimensionSpacePoint $dimensionSpacePoint): ?ContentRepository\Model\Workspace
    {
        return $this->indexedWorkspaces[$dimensionSpacePoint->getCoordinate($this->workspaceDimensionIdentifier)] ?? null;
    }

    /**
     * @param array<string, mixed> $nodeDataRow
     */
    private function extractPropertyValuesAndReferences(array $nodeDataRow, NodeType $nodeType): SerializedPropertyValuesAndReferences
    {
        $properties = [];
        $references = [];

        // Note: We use a PostgreSQL platform because the implementation is forward-compatible, @see JsonArrayType::convertToPHPValue()
        try {
            $decodedProperties = (new JsonArrayType())->convertToPHPValue($nodeDataRow['properties'], new PostgreSQLPlatform());
        } catch (ConversionException $exception) {
            throw new MigrationException(sprintf('Failed to decode properties %s of node "%s" (type: "%s"): %s', json_encode($nodeDataRow['properties']), $nodeDataRow['identifier'], $nodeType->name->value, $exception->getMessage()), 1695391558, $exception);
        }
        if (!is_array($decodedProperties)) {
            throw new MigrationException(sprintf('Failed to decode properties %s of node "%s" (type: "%s")', json_encode($nodeDataRow['properties']), $nodeDataRow['identifier'], $nodeType->name->value), 1656057035);
        }

        foreach ($decodedProperties as $propertyName => $propertyValue) {
            if ($nodeType->hasReference($propertyName)) {
                if (!empty($propertyValue)) {
                    if (!is_array($propertyValue)) {
                        $propertyValue = [$propertyValue];
                    }
                    $references[$propertyName] = NodeAggregateIds::fromArray(array_map(static fn (string $identifier) => NodeAggregateId::fromString($identifier), $propertyValue));
                }
                continue;
            }

            if (!$nodeType->hasProperty($propertyName)) {
                #$this->dispatch(Severity::WARNING, 'Skipped node data processing for the property "%s". The property name is not part of the NodeType schema for the NodeType "%s". (Node: %s)', $propertyName, $nodeType->name->value, $nodeDataRow['identifier']);
                continue;
            }
            $type = $nodeType->getPropertyType($propertyName);
            // In the old `Node`, we call the property mapper to convert the returned properties from NodeData;
            // so we need to do the same here.
            try {
                // Special case for empty values (as this can break the property mapper)
                if ($propertyValue === '' || $propertyValue === null) {
                    $properties[$propertyName] = null;
                } else {
                    $properties[$propertyName] = $this->propertyMapper->convert($propertyValue, $type);
                }

            } catch (\Exception $e) {
                throw new MigrationException(sprintf('Failed to convert property "%s" of type "%s" (Node: %s): %s', $propertyName, $type, $nodeDataRow['identifier'], $e->getMessage()), 1655912878, $e);
            }
        }

        // hiddenInIndex is stored as separate column in the nodedata table, but we need it as property
        if ($nodeDataRow['hiddeninindex']) {
            $properties['hiddenInMenu'] = true;
        }

        if ($nodeType->isOfType(NodeTypeName::fromString('Neos.TimeableNodeVisibility:Timeable'))) {
            // hiddenbeforedatetime is stored as separate column in the nodedata table, but we need it as property
            if ($nodeDataRow['hiddenbeforedatetime']) {
                $properties['enableAfterDateTime'] = $nodeDataRow['hiddenbeforedatetime'];
            }
            // hiddenafterdatetime is stored as separate column in the nodedata table, but we need it as property
            if ($nodeDataRow['hiddenafterdatetime']) {
                $properties['disableAfterDateTime'] = $nodeDataRow['hiddenafterdatetime'];
            }
        } else {
            if ($nodeDataRow['hiddenbeforedatetime'] || $nodeDataRow['hiddenafterdatetime']) {
                #$this->dispatch(Severity::WARNING, 'Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them.');
            }
        }

        return new SerializedPropertyValuesAndReferences($this->propertyConverter->serializePropertyValues(PropertyValuesToWrite::fromArray($properties)->withoutUnsets(), $nodeType), $references);
    }

    /**
     * Determines actual hidden state based on "hidden", "hiddenafterdatetime" and "hiddenbeforedatetime"
     *
     * @param array<string, mixed> $nodeDataRow
     */
    private function isNodeHidden(array $nodeDataRow): bool
    {
        // Already hidden
        if ($nodeDataRow['hidden']) {
            return true;
        }

        $now = new \DateTimeImmutable();
        $hiddenAfterDateTime = $nodeDataRow['hiddenafterdatetime'] ? new \DateTimeImmutable($nodeDataRow['hiddenafterdatetime']) : null;
        $hiddenBeforeDateTime = $nodeDataRow['hiddenbeforedatetime'] ? new \DateTimeImmutable($nodeDataRow['hiddenbeforedatetime']) : null;

        // Hidden after a date time, without getting already re-enabled by hidden before date time - afterward
        if ($hiddenAfterDateTime != null
            && $hiddenAfterDateTime < $now
            && (
                $hiddenBeforeDateTime == null
                || $hiddenBeforeDateTime > $now
                || $hiddenBeforeDateTime <= $hiddenAfterDateTime
            )
        ) {
            return true;
        }

        // Hidden before a date time, without getting enabled by hidden after date time - before
        if ($hiddenBeforeDateTime != null
            && $hiddenBeforeDateTime > $now
            && (
                $hiddenAfterDateTime == null
                || $hiddenAfterDateTime > $hiddenBeforeDateTime
            )
        ) {
            return true;
        }

        return false;
    }
}
