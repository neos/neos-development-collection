<?php
/** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration;

use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\VariantType;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMappings;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMappings;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\SucceedingSiblingNodeMoveDestination;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\SerializedPropertyValuesAndReferences;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\VisitedNodeAggregate;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\VisitedNodeAggregates;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Flow\Property\PropertyMapper;
use Ramsey\Uuid\Uuid;
use Webmozart\Assert\Assert;

final class NodeDataToEventsProcessor implements ProcessorInterface
{

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

    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly PropertyConverter $propertyConverter,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly EventNormalizer $eventNormalizer,
        private readonly Filesystem $files,
        private readonly iterable $nodeDataRows,
    ) {
        $this->sitesNodeTypeName = NodeTypeName::fromString('Neos.Neos:Sites');
        $this->contentStreamId = ContentStreamId::create();
        $this->visitedNodes = new VisitedNodeAggregates();
    }

    public function setContentStreamId(ContentStreamId $contentStreamId): void
    {
        $this->contentStreamId = $contentStreamId;
    }

    public function setSitesNodeType(NodeTypeName $nodeTypeName): void
    {
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
        $this->eventFileResource = fopen('php://temp/maxmemory:5242880', 'rb+');
        Assert::resource($this->eventFileResource, null, 'Failed to create temporary event file resource');
    }

    private function exportEvent(EventInterface $event): void
    {
        $exportedEvent = new ExportedEvent(
            Uuid::uuid4()->toString(),
            $this->eventNormalizer->getEventType($event)->value,
            json_decode($this->eventNormalizer->getEventData($event)->value, true),
            []
        );
        fwrite($this->eventFileResource, $exportedEvent->toJson() . chr(10));
        $this->numberOfExportedEvents ++;
    }

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
     * @param array $nodeDataRow
     */
    private function processNodeData(array $nodeDataRow): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeDataRow['identifier']);
        $nodePath = NodePath::fromString(strtolower($nodeDataRow['path']));
        try {
            $dimensionArray = json_decode($nodeDataRow['dimensionvalues'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new MigrationException(sprintf('Failed to parse dimensionvalues "%s": %s', $nodeDataRow['dimensionvalues'], $exception->getMessage()), 1652967873, $exception);
        }
        /** @noinspection PhpDeprecationInspection */
        $originDimensionSpacePoint = OriginDimensionSpacePoint::fromLegacyDimensionArray($dimensionArray);
        $parentNodeAggregate = $this->visitedNodes->findMostSpecificParentNodeInDimensionGraph($nodePath, $originDimensionSpacePoint, $this->interDimensionalVariationGraph);
        if ($parentNodeAggregate === null) {
            throw new MigrationException(sprintf('Failed to find parent node for node with id "%s" and dimensions: %s. Did you properly configure your dimensions setup to be in sync with the old setup?', $nodeAggregateId, $originDimensionSpacePoint), 1655980069);
        }
        $pathParts = $nodePath->getParts();
        $nodeName = end($pathParts);
        $nodeTypeName = NodeTypeName::fromString($nodeDataRow['nodetype']);
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        $serializedPropertyValuesAndReferences = $this->extractPropertyValuesAndReferences($nodeDataRow, $nodeType);

        if ($this->isAutoCreatedChildNode($parentNodeAggregate->nodeTypeName, $nodeName) && !$this->visitedNodes->containsNodeAggregate($nodeAggregateId)) {
            // Create tethered node if the node was not found before.
            // If the node was already visited, we want to create a node variant (and keep the tethering status)
            $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateId)->toDimensionSpacePointSet());
            $this->exportEvent(new NodeAggregateWithNodeWasCreated($this->contentStreamId, $nodeAggregateId, $nodeTypeName, $originDimensionSpacePoint, $specializations, $parentNodeAggregate->nodeAggregateId, $nodeName, $serializedPropertyValuesAndReferences->serializedPropertyValues, NodeAggregateClassification::CLASSIFICATION_TETHERED, null));
        } elseif ($this->visitedNodes->containsNodeAggregate($nodeAggregateId)) {
            // Create node variant, BOTH for tethered and regular nodes
            $this->createNodeVariant($nodeAggregateId, $originDimensionSpacePoint, $serializedPropertyValuesAndReferences, $parentNodeAggregate);
        } else {
            // create node aggregate
            $this->exportEvent( new NodeAggregateWithNodeWasCreated($this->contentStreamId, $nodeAggregateId, $nodeTypeName, $originDimensionSpacePoint, $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint()), $parentNodeAggregate->nodeAggregateId, $nodeName, $serializedPropertyValuesAndReferences->serializedPropertyValues, NodeAggregateClassification::CLASSIFICATION_REGULAR, null));
        }
        // nodes are hidden via NodeAggregateWasDisabled event
        if ($nodeDataRow['hidden']) {
            $this->exportEvent( new NodeAggregateWasDisabled($this->contentStreamId, $nodeAggregateId, $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateId)->toDimensionSpacePointSet())));
        }
        foreach ($serializedPropertyValuesAndReferences->references as $referencePropertyName => $destinationNodeAggregateIds) {
            $this->nodeReferencesWereSetEvents[] = new NodeReferencesWereSet($this->contentStreamId, $nodeAggregateId, new OriginDimensionSpacePointSet([$originDimensionSpacePoint]), ReferenceName::fromString($referencePropertyName), SerializedNodeReferences::fromNodeAggregateIds($destinationNodeAggregateIds));
        }

        $this->visitedNodes->add($nodeAggregateId, new DimensionSpacePointSet([$originDimensionSpacePoint->toDimensionSpacePoint()]), $nodeTypeName, $nodePath, $parentNodeAggregate->nodeAggregateId);
    }

    public function extractPropertyValuesAndReferences(array $nodeDataRow, NodeType $nodeType): SerializedPropertyValuesAndReferences
    {
        $properties = [];
        $references = [];

        // Note: We use a PostgreSQL platform because the implementation is forward-compatible, @see JsonArrayType::convertToPHPValue()
        $decodedProperties = (new JsonArrayType())->convertToPHPValue($nodeDataRow['properties'], new PostgreSQL100Platform());
        if (!is_array($decodedProperties)) {
            throw new MigrationException(sprintf('Failed to decode properties %s of node "%s" (type: "%s")', json_encode($nodeDataRow['properties']), $nodeDataRow['identifier'], $nodeType), 1656057035);
        }

        foreach ($decodedProperties as $propertyName => $propertyValue) {
            $type = $nodeType->getPropertyType($propertyName);

            if ($type === 'reference' || $type === 'references') {
                if (!empty($propertyValue)) {
                    if (!is_array($propertyValue)) {
                        $propertyValue = [$propertyValue];
                    }
                    $references[$propertyName] = NodeAggregateIds::fromArray(array_map(static fn (string $identifier) => NodeAggregateId::fromString($identifier), $propertyValue));
                }
                continue;
            }

            // In the old `Node`, we call the property mapper to convert the returned properties from NodeData;
            // so we need to do the same here.
            try {
                $properties[$propertyName] = $this->propertyMapper->convert($propertyValue, $type);
            } catch (\Exception $e) {
                throw new MigrationException(sprintf('Failed to convert property "%s" of type "%s" (Node: %s): %s', $propertyName, $type, $nodeDataRow['identifier'], $e->getMessage()), 1655912878, $e);
            }
        }

        // hiddenInIndex is stored as separate column in the nodedata table, but we need it as (internal) property
        if ($nodeDataRow['hiddeninindex']) {
            $properties['_hiddenInIndex'] = true;
        }

        return new SerializedPropertyValuesAndReferences($this->propertyConverter->serializePropertyValues(PropertyValuesToWrite::fromArray($properties), $nodeType), $references);
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
                VariantType::TYPE_SPECIALIZATION => new NodeSpecializationVariantWasCreated($this->contentStreamId, $nodeAggregateId, $alreadyVisitedOriginDimensionSpacePoint, $originDimensionSpacePoint, $coveredDimensionSpacePoints),
                VariantType::TYPE_GENERALIZATION => new NodeGeneralizationVariantWasCreated($this->contentStreamId, $nodeAggregateId, $alreadyVisitedOriginDimensionSpacePoint, $originDimensionSpacePoint, $coveredDimensionSpacePoints),
                VariantType::TYPE_PEER => new NodePeerVariantWasCreated($this->contentStreamId, $nodeAggregateId, $alreadyVisitedOriginDimensionSpacePoint, $originDimensionSpacePoint, $coveredDimensionSpacePoints),
                VariantType::TYPE_SAME => null,
            };
            $variantSourceOriginDimensionSpacePoint = $alreadyVisitedOriginDimensionSpacePoint;
            if ($variantCreatedEvent instanceof NodeSpecializationVariantWasCreated || $variantCreatedEvent instanceof NodeGeneralizationVariantWasCreated) {
                break;
            }
        }
        if ($variantCreatedEvent === null) {
            throw new MigrationException(sprintf('Node "%s" for dimension %s was already created previously', $nodeAggregateId, $originDimensionSpacePoint), 1656057201);
        }
        $this->exportEvent($variantCreatedEvent);
        if ($serializedPropertyValuesAndReferences->serializedPropertyValues->count() > 0) {
            $this->exportEvent(new NodePropertiesWereSet($this->contentStreamId, $nodeAggregateId, $originDimensionSpacePoint, $serializedPropertyValuesAndReferences->serializedPropertyValues));
        }
        // When we specialize/generalize, we create a node variant at exactly the same tree location as the source node
        // If the parent node aggregate id differs, we need to move the just created variant to the new location
        $nodeAggregate = $this->visitedNodes->getByNodeAggregateId($nodeAggregateId);
        if (!$parentNodeAggregate->nodeAggregateId->equals($nodeAggregate->getVariant($variantSourceOriginDimensionSpacePoint)->parentNodeAggregateId)) {
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
        if (!$this->nodeTypeManager->hasNodeType($parentNodeTypeName)) {
            return false;
        }
        $nodeTypeOfParent = $this->nodeTypeManager->getNodeType($parentNodeTypeName);
        return $nodeTypeOfParent->hasAutoCreatedChildNode($nodeName);
    }
}
