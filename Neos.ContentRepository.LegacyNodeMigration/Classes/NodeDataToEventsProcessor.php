<?php
/** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration;

use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Doctrine\DBAL\Types\ConversionException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\VariantType;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMappings;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMappings;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\SucceedingSiblingNodeMoveDestination;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\SerializedPropertyValuesAndReferences;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\VisitedNodeAggregate;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\VisitedNodeAggregates;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Flow\Property\PropertyMapper;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Ramsey\Uuid\Uuid;
use Webmozart\Assert\Assert;

final class NodeDataToEventsProcessor implements ProcessorInterface
{
    /**
     * @var array<\Closure>
     */
    private array $callbacks = [];
    private NodeTypeName $sitesNodeTypeName;
    private ContentStreamId $contentStreamId;
    private VisitedNodeAggregates $visitedNodes;

    /**
     * @var NodeReferencesWereSet[]
     */
    private array $nodeReferencesWereSetEvents = [];

    private int $numberOfExportedEvents = 0;

    private bool $metaDataExported = false;

    /**
     * @var resource|null
     */
    private $eventFileResource;

    /**
     * @param iterable<int, array<string, mixed>> $nodeDataRows
     */
    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly PropertyConverter $propertyConverter,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly EventNormalizer $eventNormalizer,
        private readonly Filesystem $files,
        private readonly iterable $nodeDataRows,
    ) {
        $this->sitesNodeTypeName = NodeTypeNameFactory::forSites();
        $this->contentStreamId = ContentStreamId::create();
        $this->visitedNodes = new VisitedNodeAggregates();
    }

    public function setContentStreamId(ContentStreamId $contentStreamId): void
    {
        $this->contentStreamId = $contentStreamId;
    }

    public function setSitesNodeType(NodeTypeName $nodeTypeName): void
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        if (!$nodeType?->isOfType(NodeTypeNameFactory::NAME_SITES)) {
            throw new \InvalidArgumentException(
                sprintf('Sites NodeType "%s" must be of type "%s"', $nodeTypeName->value, NodeTypeNameFactory::NAME_SITES),
                1695802415
            );
        }
        $this->sitesNodeTypeName = $nodeTypeName;
    }

    public function onMessage(\Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    public function run(): ProcessorResult
    {
        $this->resetRuntimeState();

        foreach ($this->nodeDataRows as $nodeDataRow) {
            if ($nodeDataRow['path'] === '/sites') {
                $sitesNodeAggregateId = NodeAggregateId::fromString($nodeDataRow['identifier']);
                $this->visitedNodes->addRootNode($sitesNodeAggregateId, $this->sitesNodeTypeName, NodePath::fromString('/sites'), $this->interDimensionalVariationGraph->getDimensionSpacePoints());
                $this->exportEvent(new RootNodeAggregateWithNodeWasCreated($this->contentStreamId, $sitesNodeAggregateId, $this->sitesNodeTypeName, $this->interDimensionalVariationGraph->getDimensionSpacePoints(), NodeAggregateClassification::CLASSIFICATION_ROOT));
                continue;
            }
            if ($this->metaDataExported === false && $nodeDataRow['parentpath'] === '/sites') {
                $this->exportMetaData($nodeDataRow);
                $this->metaDataExported = true;
            }
            try {
                $this->processNodeData($nodeDataRow);
            } catch (MigrationException $exception) {
                return ProcessorResult::error($exception->getMessage());
            }
        }
        // Set References, now when the full import is done.
        foreach ($this->nodeReferencesWereSetEvents as $nodeReferencesWereSetEvent) {
            $this->exportEvent($nodeReferencesWereSetEvent);
        }

        try {
            $this->files->writeStream('events.jsonl', $this->eventFileResource);
        } catch (FilesystemException $exception) {
            return ProcessorResult::error(sprintf('Failed to write events.jsonl: %s', $exception->getMessage()));
        }
        return ProcessorResult::success(sprintf('Exported %d event%s', $this->numberOfExportedEvents, $this->numberOfExportedEvents === 1 ? '' : 's'));
    }

    /** ----------------------------- */

    private function resetRuntimeState(): void
    {
        $this->visitedNodes = new VisitedNodeAggregates();
        $this->nodeReferencesWereSetEvents = [];
        $this->numberOfExportedEvents = 0;
        $this->metaDataExported = false;
        $this->eventFileResource = fopen('php://temp/maxmemory:5242880', 'rb+') ?: null;
        Assert::resource($this->eventFileResource, null, 'Failed to create temporary event file resource');
    }

    private function exportEvent(EventInterface $event): void
    {
        $normalizedEvent = $this->eventNormalizer->normalize($event);
        $exportedEvent = new ExportedEvent(
            $normalizedEvent->id->value,
            $normalizedEvent->type->value,
            json_decode($normalizedEvent->data->value, true),
            [],
        );
        assert($this->eventFileResource !== null);
        fwrite($this->eventFileResource, $exportedEvent->toJson() . chr(10));
        $this->numberOfExportedEvents++;
    }

    /**
     * @param array<string, mixed> $nodeDataRow
     */
    private function exportMetaData(array $nodeDataRow): void
    {
        if ($this->files->fileExists('meta.json')) {
            $data = json_decode($this->files->read('meta.json'), true, 512, JSON_THROW_ON_ERROR);
        } else {
            $data = [];
        }
        $data['version'] = 1;
        $data['sitePackageKey'] = strtok($nodeDataRow['nodetype'], ':');
        $data['siteNodeName'] = substr($nodeDataRow['path'], 7);
        $data['siteNodeType'] = $nodeDataRow['nodetype'];
        $this->files->write('meta.json', json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /**
     * @param array<string, mixed> $nodeDataRow
     */
    private function processNodeData(array $nodeDataRow): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeDataRow['identifier']);

        try {
            $dimensionArray = json_decode($nodeDataRow['dimensionvalues'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new MigrationException(sprintf('Failed to parse dimensionvalues "%s": %s', $nodeDataRow['dimensionvalues'], $exception->getMessage()), 1652967873, $exception);
        }
        /** @noinspection PhpDeprecationInspection */
        $originDimensionSpacePoint = OriginDimensionSpacePoint::fromLegacyDimensionArray($dimensionArray);

        if ($originDimensionSpacePoint->coordinates === []) {
            // the old CR had a special case implemented: in case a node was not found in the expected dimension,
            // it was checked whether it is found in the empty dimension - and if so, this one was used.
            //
            // We need to replicate this logic here.
            //
            // Assumption: a node with empty coordinates comes AFTER the same node with coordinates.
            // This is implemented in {@see NodeDataLoader.}
            //
            // If the dimensions of the node we want to import are empty,
            // - we iterate through ALL possible dimensions (because the node in empty dimension might shine through
            //   in all dimensions).
            // - we check if we have seen a node already in the target dimension (if yes, we can ignore the empty-dimension node)
            // - if we haven't seen a node in the target dimension, we can assume it does not exist (see "assumption" above),
            //   and then create the node in the currently-iterated dimension.
            foreach ($this->interDimensionalVariationGraph->getDimensionSpacePoints() as $dimensionSpacePoint) {
                $originDimensionSpacePoint = OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint);
                if (!$this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateId)->contains($originDimensionSpacePoint)) {
                    $this->processNodeDataWithoutFallbackToEmptyDimension($nodeAggregateId, $originDimensionSpacePoint, $nodeDataRow);
                }
            }
        } else {
            $this->processNodeDataWithoutFallbackToEmptyDimension($nodeAggregateId, $originDimensionSpacePoint, $nodeDataRow);
        }
    }


    /**
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint
     * @param NodeAggregateId $nodeAggregateId
     * @param array<string, mixed> $nodeDataRow
     * @return NodeName[]|void
     * @throws \JsonException
     */
    public function processNodeDataWithoutFallbackToEmptyDimension(NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $originDimensionSpacePoint, array $nodeDataRow)
    {
        $nodePath = NodePath::fromString(strtolower($nodeDataRow['path']));
        $parentNodeAggregate = $this->visitedNodes->findMostSpecificParentNodeInDimensionGraph($nodePath, $originDimensionSpacePoint, $this->interDimensionalVariationGraph);
        if ($parentNodeAggregate === null) {
            $this->dispatch(Severity::ERROR, 'Failed to find parent node for node with id "%s" and dimensions: %s. Please ensure that the new content repository has a valid content dimension configuration. Also note that the old CR can sometimes have orphaned nodes.', $nodeAggregateId->value, $originDimensionSpacePoint->toJson());
            return;
        }
        $pathParts = $nodePath->getParts();
        $nodeName = end($pathParts);
        assert($nodeName !== false);
        $nodeTypeName = NodeTypeName::fromString($nodeDataRow['nodetype']);

        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);

        $isSiteNode = $nodeDataRow['parentpath'] === '/sites';
        if ($isSiteNode && !$nodeType?->isOfType(NodeTypeNameFactory::NAME_SITE)) {
            throw new MigrationException(sprintf(
                'The site node "%s" (type: "%s") must be of type "%s"', $nodeDataRow['identifier'], $nodeTypeName->value, NodeTypeNameFactory::NAME_SITE
            ), 1695801620);
        }

        if (!$nodeType) {
            $this->dispatch(Severity::ERROR, 'The node type "%s" is not available. Node: "%s"', $nodeTypeName->value, $nodeDataRow['identifier']);
            return;
        }

        $serializedPropertyValuesAndReferences = $this->extractPropertyValuesAndReferences($nodeDataRow, $nodeType);

        if ($this->isAutoCreatedChildNode($parentNodeAggregate->nodeTypeName, $nodeName) && !$this->visitedNodes->containsNodeAggregate($nodeAggregateId)) {
            // Create tethered node if the node was not found before.
            // If the node was already visited, we want to create a node variant (and keep the tethering status)
            $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateId)->toDimensionSpacePointSet());
            $this->exportEvent(
                new NodeAggregateWithNodeWasCreated(
                    $this->contentStreamId,
                    $nodeAggregateId,
                    $nodeTypeName,
                    $originDimensionSpacePoint,
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings($specializations),
                    $parentNodeAggregate->nodeAggregateId,
                    $nodeName,
                    $serializedPropertyValuesAndReferences->serializedPropertyValues,
                    NodeAggregateClassification::CLASSIFICATION_TETHERED,
                )
            );
        } elseif ($this->visitedNodes->containsNodeAggregate($nodeAggregateId)) {
            // Create node variant, BOTH for tethered and regular nodes
            $this->createNodeVariant($nodeAggregateId, $originDimensionSpacePoint, $serializedPropertyValuesAndReferences, $parentNodeAggregate);
        } else {
            // create node aggregate
            $this->exportEvent(
                new NodeAggregateWithNodeWasCreated(
                    $this->contentStreamId,
                    $nodeAggregateId,
                    $nodeTypeName,
                    $originDimensionSpacePoint,
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        $this->interDimensionalVariationGraph->getSpecializationSet(
                            $originDimensionSpacePoint->toDimensionSpacePoint()
                        )
                    ),
                    $parentNodeAggregate->nodeAggregateId,
                    $nodeName,
                    $serializedPropertyValuesAndReferences->serializedPropertyValues,
                    NodeAggregateClassification::CLASSIFICATION_REGULAR,
                )
            );
        }
        // nodes are hidden via SubtreeWasTagged event
        if ($this->isNodeHidden($nodeDataRow)) {
            $this->exportEvent(new SubtreeWasTagged($this->contentStreamId, $nodeAggregateId, $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateId)->toDimensionSpacePointSet()), SubtreeTag::disabled()));
        }
        foreach ($serializedPropertyValuesAndReferences->references as $referencePropertyName => $destinationNodeAggregateIds) {
            $this->nodeReferencesWereSetEvents[] = new NodeReferencesWereSet($this->contentStreamId, $nodeAggregateId, new OriginDimensionSpacePointSet([$originDimensionSpacePoint]), ReferenceName::fromString($referencePropertyName), SerializedNodeReferences::fromNodeAggregateIds($destinationNodeAggregateIds));
        }

        $this->visitedNodes->add($nodeAggregateId, new DimensionSpacePointSet([$originDimensionSpacePoint->toDimensionSpacePoint()]), $nodeTypeName, $nodePath, $parentNodeAggregate->nodeAggregateId);
    }

    /**
     * @param array<string, mixed> $nodeDataRow
     */
    public function extractPropertyValuesAndReferences(array $nodeDataRow, NodeType $nodeType): SerializedPropertyValuesAndReferences
    {
        $properties = [];
        $references = [];

        // Note: We use a PostgreSQL platform because the implementation is forward-compatible, @see JsonArrayType::convertToPHPValue()
        try {
            $decodedProperties = (new JsonArrayType())->convertToPHPValue($nodeDataRow['properties'], new PostgreSQL100Platform());
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
                $this->dispatch(Severity::WARNING, 'Skipped node data processing for the property "%s". The property name is not part of the NodeType schema for the NodeType "%s". (Node: %s)', $propertyName, $nodeType->name->value, $nodeDataRow['identifier']);
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

        // hiddenInIndex is stored as separate column in the nodedata table, but we need it as (internal) property
        if ($nodeDataRow['hiddeninindex']) {
            $properties['_hiddenInIndex'] = true;
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
                $this->dispatch(Severity::WARNING, 'Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them.');
            }
        }

        return new SerializedPropertyValuesAndReferences($this->propertyConverter->serializePropertyValues(PropertyValuesToWrite::fromArray($properties)->withoutUnsets(), $nodeType), $references);
    }

    /**
     * Produces a node variant creation event (NodeSpecializationVariantWasCreated, NodeGeneralizationVariantWasCreated or NodePeerVariantWasCreated) and the corresponding NodePropertiesWereSet event
     * if another variant of the specified node has been processed already.
     *
     * NOTE: We prioritize specializations/generalizations over peer variants ("ch" creates a specialization variant of "de" rather than a peer of "en" if both has been seen before).
     * For that reason we loop over all previously visited dimension space points until we encounter a specialization/generalization. Otherwise, the last NodePeerVariantWasCreated will be used
     */
    private function createNodeVariant(NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $originDimensionSpacePoint, SerializedPropertyValuesAndReferences $serializedPropertyValuesAndReferences, VisitedNodeAggregate $parentNodeAggregate): void
    {
        $alreadyVisitedOriginDimensionSpacePoints = $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateId);
        $coveredDimensionSpacePoints = $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $alreadyVisitedOriginDimensionSpacePoints->toDimensionSpacePointSet());
        $variantCreatedEvent = null;
        $variantSourceOriginDimensionSpacePoint = null;
        foreach ($alreadyVisitedOriginDimensionSpacePoints as $alreadyVisitedOriginDimensionSpacePoint) {
            $variantType = $this->interDimensionalVariationGraph->getVariantType($originDimensionSpacePoint->toDimensionSpacePoint(), $alreadyVisitedOriginDimensionSpacePoint->toDimensionSpacePoint());
            $variantCreatedEvent = match ($variantType) {
                VariantType::TYPE_SPECIALIZATION => new NodeSpecializationVariantWasCreated(
                    $this->contentStreamId,
                    $nodeAggregateId,
                    $alreadyVisitedOriginDimensionSpacePoint,
                    $originDimensionSpacePoint,
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        $coveredDimensionSpacePoints
                    )
                ),
                VariantType::TYPE_GENERALIZATION => new NodeGeneralizationVariantWasCreated(
                    $this->contentStreamId,
                    $nodeAggregateId,
                    $alreadyVisitedOriginDimensionSpacePoint,
                    $originDimensionSpacePoint,
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        $coveredDimensionSpacePoints,
                    )
                ),
                VariantType::TYPE_PEER => new NodePeerVariantWasCreated(
                    $this->contentStreamId,
                    $nodeAggregateId,
                    $alreadyVisitedOriginDimensionSpacePoint,
                    $originDimensionSpacePoint,
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        $coveredDimensionSpacePoints,
                    ),
                ),
                VariantType::TYPE_SAME => null,
            };
            $variantSourceOriginDimensionSpacePoint = $alreadyVisitedOriginDimensionSpacePoint;
            if ($variantCreatedEvent instanceof NodeSpecializationVariantWasCreated || $variantCreatedEvent instanceof NodeGeneralizationVariantWasCreated) {
                break;
            }
        }
        if ($variantCreatedEvent === null) {
            throw new MigrationException(sprintf('Node "%s" for dimension %s was already created previously', $nodeAggregateId->value, $originDimensionSpacePoint->toJson()), 1656057201);
        }
        $this->exportEvent($variantCreatedEvent);
        if ($serializedPropertyValuesAndReferences->serializedPropertyValues->count() > 0) {
            $this->exportEvent(
                new NodePropertiesWereSet(
                    $this->contentStreamId,
                    $nodeAggregateId,
                    $originDimensionSpacePoint,
                    $coveredDimensionSpacePoints,
                    $serializedPropertyValuesAndReferences->serializedPropertyValues,
                    PropertyNames::createEmpty()
                )
            );
        }
        // When we specialize/generalize, we create a node variant at exactly the same tree location as the source node
        // If the parent node aggregate id differs, we need to move the just created variant to the new location
        $nodeAggregate = $this->visitedNodes->getByNodeAggregateId($nodeAggregateId);
        if (
            $variantSourceOriginDimensionSpacePoint &&
            !$parentNodeAggregate->nodeAggregateId->equals($nodeAggregate->getVariant($variantSourceOriginDimensionSpacePoint)->parentNodeAggregateId)
        ) {
            $this->exportEvent(new NodeAggregateWasMoved(
                $this->contentStreamId,
                $nodeAggregateId,
                OriginNodeMoveMappings::fromArray([
                    new OriginNodeMoveMapping(
                        $originDimensionSpacePoint,
                        CoverageNodeMoveMappings::create(
                            CoverageNodeMoveMapping::createForNewSucceedingSibling(
                                $originDimensionSpacePoint->toDimensionSpacePoint(),
                                SucceedingSiblingNodeMoveDestination::create(
                                    $parentNodeAggregate->nodeAggregateId,
                                    $variantSourceOriginDimensionSpacePoint,

                                    $nodeAggregate->getVariant($variantSourceOriginDimensionSpacePoint)->parentNodeAggregateId,
                                    $nodeAggregate->getVariant($variantSourceOriginDimensionSpacePoint)->originDimensionSpacePoint
                                )
                            )
                        )
                    )
                ])
            ));
        }
    }

    private function isAutoCreatedChildNode(NodeTypeName $parentNodeTypeName, NodeName $nodeName): bool
    {
        $nodeTypeOfParent = $this->nodeTypeManager->getNodeType($parentNodeTypeName);
        if (!$nodeTypeOfParent) {
            return false;
        }
        return $nodeTypeOfParent->hasTetheredNode($nodeName);
    }

    private function dispatch(Severity $severity, string $message, mixed ...$args): void
    {
        $renderedMessage = sprintf($message, ...$args);
        foreach ($this->callbacks as $callback) {
            $callback($severity, $renderedMessage);
        }
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
                || $hiddenBeforeDateTime<= $hiddenAfterDateTime
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
