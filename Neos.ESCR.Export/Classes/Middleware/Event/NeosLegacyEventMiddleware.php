<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Event;

use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemException;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\VariantType;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\ESCR\Export\Middleware\Context;
use Neos\ESCR\Export\Middleware\Event\ValueObject\Attributes;
use Neos\ESCR\Export\Middleware\Event\ValueObject\ExportedEvent;
use Neos\ESCR\Export\Middleware\Event\ValueObject\NodeAggregateIdentifierAndNodeTypeForLegacyImport;
use Neos\ESCR\Export\Middleware\Event\ValueObject\SerializedPropertyValuesAndReferences;
use Neos\ESCR\Export\Middleware\MiddlewareInterface;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Flow\Property\PropertyMapper;
use Ramsey\Uuid\Uuid;

final class NeosLegacyEventMiddleware implements MiddlewareInterface
{
    /**
     * @var array<string, NodeAggregateIdentifierAndNodeTypeForLegacyImport>
     */
    private array $visitedNodeIdentifiersAndTypeByPathAndDimensionSpacePoint = [];
    private array $nodeDatasToExportAtNextIteration = [];

    /**
     * @var array<string, OriginDimensionSpacePointSet>
     */
    private array $visitedOriginDimensionSpacePointsByNodeAggregateIdentifier = [];
    private NodeAggregateIdentifierAndNodeTypeForLegacyImport $nodeAggregateIdentifierForSitesNode;
    private ContentStreamIdentifier $contentStreamIdentifier;
    private int $sequenceNumber = 1;
    private array $streamVersions = [];
    /**
     * @var resource|null
     */
    private $eventFileResource;
    /**
     * @var NodeReferencesWereSet[]
     */
    private array $nodeReferencesWereSetEvents = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly EventNormalizer $eventNormalizer,
        private readonly PropertyConverter $propertyConverter,
    ) {
        $this->nodeAggregateIdentifierForSitesNode = new NodeAggregateIdentifierAndNodeTypeForLegacyImport(
            NodeAggregateIdentifier::create(),
            NodeTypeName::fromString('Neos.Neos:Sites')
        );
        $this->contentStreamIdentifier = ContentStreamIdentifier::create();
    }

    public function getLabel(): string
    {
        return 'Neos Legacy Content Repository events';
    }

    public function processImport(Context $context): void
    {
        throw new \RuntimeException('Importing to lecacy system is not yet supported', 1652947289);
    }

    public function processExport(Context $context): void
    {
        $this->resetRuntimeState();
        $this->eventFileResource = fopen('php://temp/maxmemory:5242880', 'rb+');
        if ($this->eventFileResource === false) {
            throw new \RuntimeException('Failed to create temporary event file resource', 1652876509);
        }
        $this->createRootNode();


        $query = $this->connection->executeQuery('
            SELECT
                *
            FROM
                neos_contentrepository_domain_model_nodedata
            WHERE
                workspace = \'live\'
                AND (movedto IS NULL OR removed=0)
                AND path NOT IN (\'sites\', \'/\')
            ORDER BY
                parentpath, sortingindex
        ');
        foreach ($query->iterateAssociative() as $nodeDataRow) {
            $this->exportNodeData($nodeDataRow);
        }
        while ($this->nodeDatasToExportAtNextIteration !== []) {
            $nodeDataRow = array_shift($this->nodeDatasToExportAtNextIteration);
            $this->exportNodeData($nodeDataRow);
        }

        // Set References, now when the full import is done.
        $this->exportEvents($this->contentStreamName(), ...$this->nodeReferencesWereSetEvents);

        try {
            $context->files->writeStream('events.jsonl', $this->eventFileResource);
        } catch (FilesystemException $e) {
            throw new \RuntimeException(sprintf('Failed to write events.jsonl: %s', $e->getMessage()), 1646326885, $e);
        }
        fclose($this->eventFileResource);
        unset($this->eventFileResource);
//        $context->report(sprintf('Exported %d event%s', $numberOfExportedEvents, $numberOfExportedEvents === 1 ? '' : 's'));
    }

    /** --------------------------- */

    private function resetRuntimeState(): void
    {
        $this->visitedNodeIdentifiersAndTypeByPathAndDimensionSpacePoint = [];
        $this->visitedOriginDimensionSpacePointsByNodeAggregateIdentifier = [];
        $this->sequenceNumber = 1;
        $this->streamVersions = [];
        $this->nodeReferencesWereSetEvents = [];
    }

    private function contentStreamName(): StreamName
    {
        return StreamName::fromString('Neos.ContentRepository:ContentStream:' . $this->contentStreamIdentifier);
    }

    private function exportNodeData(array $nodeDataRow): void
    {
        $nodePath = NodePath::fromString(strtolower($nodeDataRow['path']));
        try {
            $dimensionArray = json_decode($nodeDataRow['dimensionvalues'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(sprintf('Failed to parse dimensionvalues "%s": %s', $nodeDataRow['dimensionvalues'], $exception->getMessage()), 1652967873, $exception);
        }
        $originDimensionSpacePoint = OriginDimensionSpacePoint::fromLegacyDimensionArray($dimensionArray);
        $parentNodeAggregateIdentifierAndNodeType = $this->findParentNodeAggregateIdentifierAndNodeType(NodePath::fromString($nodeDataRow['parentpath']), $originDimensionSpacePoint->toDimensionSpacePoint());
        if ($parentNodeAggregateIdentifierAndNodeType === null) {
            // TODO cleanup?
            if ($nodeDataRow['parentpath'] !== '/') {
                $this->nodeDatasToExportAtNextIteration[] = $nodeDataRow;
            }
            return;
        }

        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeDataRow['identifier']);
        $pathParts = $nodePath->getParts();
        $nodeName = end($pathParts);
        $nodeTypeName = NodeTypeName::fromString($nodeDataRow['nodetype']);
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->getValue());
        // HACK: $nodeType->getPropertyType() is missing the "initialize" call,
        // so we need to trigger another method beforehand.
        $nodeType->getFullConfiguration();
        $serializedPropertyValuesAndReferences = $this->extractPropertyValuesAndReferences($nodeDataRow, $nodeType);

        if ($this->isAutoCreatedChildNode($parentNodeAggregateIdentifierAndNodeType->nodeTypeName, $nodeName)) {
            $this->createTetheredNode($nodeAggregateIdentifier, $originDimensionSpacePoint, $nodeTypeName, $parentNodeAggregateIdentifierAndNodeType->nodeAggregateIdentifier, $nodeName, $serializedPropertyValuesAndReferences->serializedPropertyValues);
        } elseif ($this->nodeAggregateWasCreatedAlready($nodeAggregateIdentifier)) {
            $this->createNodeVariant($nodeAggregateIdentifier, $originDimensionSpacePoint, $serializedPropertyValuesAndReferences);
        } else {
            $this->createNodeAggregate($nodeAggregateIdentifier, $nodeTypeName, $originDimensionSpacePoint, $parentNodeAggregateIdentifierAndNodeType, $nodeName, $serializedPropertyValuesAndReferences);
        }
        if ($nodeDataRow['hidden']) {
            $this->disableNode($nodeAggregateIdentifier, $originDimensionSpacePoint);
        }

        $this->recordVisitedNode($nodePath, $originDimensionSpacePoint, $nodeAggregateIdentifier, $nodeTypeName);
    }

    private function extractPropertyValuesAndReferences(array $nodeDataRow, NodeType $nodeType): SerializedPropertyValuesAndReferences
    {
        $properties = [];
        $references = [];

        $decodedProperties = (new JsonArrayType())->convertToPHPValue($nodeDataRow['properties'], $this->connection->getDatabasePlatform());

        foreach ($decodedProperties as $propertyName => $propertyValue) {
            $type = $nodeType->getPropertyType($propertyName);

            if ($type === 'reference' || $type === 'references') {
                if (!empty($propertyValue)) {
                    if (!is_array($propertyValue)) {
                        $propertyValue = [$propertyValue];
                    }
                    $references[$propertyName] = NodeAggregateIdentifiers::fromArray(array_map(static fn (string $identifier) => NodeAggregateIdentifier::fromString($identifier), $propertyValue));
                }
                continue;
            }

            // In the old `NodeInterface`, we call the property mapper to convert the returned properties from NodeData;
            // so we need to do the same here.
            $properties[$propertyName] = $this->propertyMapper->convert($propertyValue, $type);
        }

        // hiddenInIndex is stored as separate column in the nodedata table, but we need it as (internal) property
        if ($nodeDataRow['hiddeninindex']) {
            $properties['_hiddenInIndex'] = true;
        }

        return new SerializedPropertyValuesAndReferences(
            $this->propertyConverter->serializePropertyValues(PropertyValuesToWrite::fromArray($properties), $nodeType),
            $references
        );
    }

    private function nodeAggregateWasCreatedAlready(NodeAggregateIdentifier $nodeAggregateIdentifier): bool
    {
        return isset($this->visitedOriginDimensionSpacePointsByNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()]);
    }

    private function createNodeVariant(NodeAggregateIdentifier $nodeAggregateIdentifier, OriginDimensionSpacePoint $originDimensionSpacePoint, SerializedPropertyValuesAndReferences $serializedPropertyValuesAndReferences): void
    {
        $alreadyVisitedOriginDimensionSpacePoints = $this->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateIdentifier);
        $coveredDimensionSpacePoints = $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $alreadyVisitedOriginDimensionSpacePoints->toDimensionSpacePointSet());
        $variantCreatedEvent = null;
        foreach ($alreadyVisitedOriginDimensionSpacePoints as $alreadyVisitedOriginDimensionSpacePoint) {
            $variantType = $this->interDimensionalVariationGraph->getVariantType($originDimensionSpacePoint->toDimensionSpacePoint(), $alreadyVisitedOriginDimensionSpacePoint->toDimensionSpacePoint());
            $variantCreatedEvent = match ($variantType) {
                VariantType::TYPE_SPECIALIZATION => new NodeSpecializationVariantWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $alreadyVisitedOriginDimensionSpacePoint, $originDimensionSpacePoint, $coveredDimensionSpacePoints, UserIdentifier::forSystemUser()),
                VariantType::TYPE_GENERALIZATION => new NodeGeneralizationVariantWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $alreadyVisitedOriginDimensionSpacePoint, $originDimensionSpacePoint, $coveredDimensionSpacePoints, UserIdentifier::forSystemUser()),
                VariantType::TYPE_PEER => new NodePeerVariantWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $alreadyVisitedOriginDimensionSpacePoint, $originDimensionSpacePoint, $coveredDimensionSpacePoints, UserIdentifier::forSystemUser()),
                VariantType::TYPE_SAME => null,
            };
            if ($variantCreatedEvent instanceof NodeSpecializationVariantWasCreated || $variantCreatedEvent instanceof NodeGeneralizationVariantWasCreated) {
                break;
            }
        }
        if ($variantCreatedEvent !== null) {
            $this->exportEvents($this->contentStreamName(), $variantCreatedEvent, new NodePropertiesWereSet($this->contentStreamIdentifier, $nodeAggregateIdentifier, $originDimensionSpacePoint, $serializedPropertyValuesAndReferences->serializedPropertyValues, UserIdentifier::forSystemUser()));
        }
        // TODO parent node aggregate id is different? => create node aggregate or move node??
    }

    private function alreadyVisitedOriginDimensionSpacePoints(NodeAggregateIdentifier $nodeAggregateIdentifier): OriginDimensionSpacePointSet
    {
        return $this->visitedOriginDimensionSpacePointsByNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()] ?? OriginDimensionSpacePointSet::fromArray([]);
    }


    private function createNodeAggregate(NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeName $nodeTypeName, OriginDimensionSpacePoint $originDimensionSpacePoint, NodeAggregateIdentifierAndNodeTypeForLegacyImport $parentNodeAggregateIdentifierAndNodeType, bool|NodeName $nodeName, SerializedPropertyValuesAndReferences $serializedPropertyValuesAndReferences): void
    {
        $this->exportEvents($this->contentStreamName(), new NodeAggregateWithNodeWasCreated(
            $this->contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $nodeTypeName,
            $originDimensionSpacePoint,
            $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint()),
            $parentNodeAggregateIdentifierAndNodeType->nodeAggregateIdentifier,
            $nodeName,
            $serializedPropertyValuesAndReferences->serializedPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            UserIdentifier::forSystemUser(),
            // TODO determine succeedingNodeAggregateIdentifier
            null)
        );
    }

    private function isAutoCreatedChildNode(NodeTypeName $parentNodeTypeName, NodeName $nodeName): bool
    {
        if (!$this->nodeTypeManager->hasNodeType($parentNodeTypeName->getValue())) {
            return false;
        }
        $nodeTypeOfParent = $this->nodeTypeManager->getNodeType($parentNodeTypeName->getValue());
        return $nodeTypeOfParent->hasAutoCreatedChildNode($nodeName);
    }

    private function createTetheredNode(NodeAggregateIdentifier $nodeAggregateIdentifier, OriginDimensionSpacePoint $originDimensionSpacePoint, NodeTypeName $nodeTypeName, NodeAggregateIdentifier $parentNodeAggregateIdentifier, NodeName $nodeName, SerializedPropertyValues $serializedPropertyValues): void
    {
        $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateIdentifier)->toDimensionSpacePointSet());
        $event = new NodeAggregateWithNodeWasCreated(
            $this->contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $nodeTypeName,
            $originDimensionSpacePoint,
            $specializations,
            $parentNodeAggregateIdentifier,
            $nodeName,
            $serializedPropertyValues,
            NodeAggregateClassification::CLASSIFICATION_TETHERED,
            UserIdentifier::forSystemUser(),
            // TODO determine succeedingNodeAggregateIdentifier
            null
        );
        $this->exportEvents($this->contentStreamName(), $event);
    }

    private function disableNode(NodeAggregateIdentifier $nodeAggregateIdentifier, OriginDimensionSpacePoint $originDimensionSpacePoint): void
    {
        $this->exportEvents($this->contentStreamName(), new NodeAggregateWasDisabled($this->contentStreamIdentifier, $nodeAggregateIdentifier, $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateIdentifier)->toDimensionSpacePointSet()), UserIdentifier::forSystemUser(),));
    }


    private function findParentNodeAggregateIdentifierAndNodeType(NodePath $parentPath, DimensionSpacePoint $dimensionSpacePoint): ?NodeAggregateIdentifierAndNodeTypeForLegacyImport
    {
        if ($parentPath->jsonSerialize() === '/sites') {
            return $this->nodeAggregateIdentifierForSitesNode;
        }
        while ($dimensionSpacePoint !== null) {
            $parentPathAndDimensionSpacePointHash = strtolower($parentPath->jsonSerialize()) . '__' . $dimensionSpacePoint->hash;
            if (isset($this->visitedNodeIdentifiersAndTypeByPathAndDimensionSpacePoint[$parentPathAndDimensionSpacePointHash])) {
                return $this->visitedNodeIdentifiersAndTypeByPathAndDimensionSpacePoint[$parentPathAndDimensionSpacePointHash];
            }
            $dimensionSpacePoint = $this->interDimensionalVariationGraph->getPrimaryGeneralization($dimensionSpacePoint);
        }
        return null;
    }

    private function recordVisitedNode(NodePath $nodePath, OriginDimensionSpacePoint $originDimensionSpacePoint, NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeName $nodeTypeName): void
    {
        $pathAndDimensionSpacePointHash = strtolower($nodePath->jsonSerialize()) . '__' . $originDimensionSpacePoint->hash;
        if (isset($this->nodeAggregateIdentifiers[$pathAndDimensionSpacePointHash])) {
            throw new \RuntimeException(sprintf('Node "%s" with path "%s" and dimension space point "%s" was already visited before', $nodeAggregateIdentifier, $nodePath, $originDimensionSpacePoint), 1653050442);
        }
        $this->visitedNodeIdentifiersAndTypeByPathAndDimensionSpacePoint[$pathAndDimensionSpacePointHash] = new NodeAggregateIdentifierAndNodeTypeForLegacyImport($nodeAggregateIdentifier, $nodeTypeName);
        $this->visitedOriginDimensionSpacePointsByNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()] = $this->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateIdentifier)->getUnion(new OriginDimensionSpacePointSet([$originDimensionSpacePoint]));
    }

    private function createRootNode(): void
    {
        $dimensionSpacePointSet = $this->contentDimensionZookeeper->getAllowedDimensionSubspace();
        $event = new RootNodeAggregateWithNodeWasCreated(
            $this->contentStreamIdentifier,
            $this->nodeAggregateIdentifierForSitesNode->nodeAggregateIdentifier,
            $this->nodeAggregateIdentifierForSitesNode->nodeTypeName,
            $dimensionSpacePointSet,
            NodeAggregateClassification::CLASSIFICATION_ROOT,
            UserIdentifier::forSystemUser()
        );
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($this->contentStreamIdentifier)->getEventStreamName();
        $this->exportEvents($streamName, $event);
    }

    private function exportEvents(StreamName $streamName, DomainEventInterface ...$events): void
    {
        $attributes = Attributes::create()->withMetadata()->withRecordedAt();
        $now = new \DateTimeImmutable();
        foreach ($events as $event) {
            $eventIdentifier = null;
            $metadata = [];
            if ($event instanceof DecoratedEvent) {
                $eventIdentifier = $event->hasIdentifier() ? $event->getIdentifier() : null;
                $metadata = $event->getMetadata();
                $event = $event->getWrappedEvent();
            }
            if (!isset($this->streamVersions[(string)$streamName])) {
                $this->streamVersions[(string)$streamName] = 0;
            }
            $rawEvent = new RawEvent(
                $this->sequenceNumber ++,
                $this->eventNormalizer->getEventType($event),
                $this->eventNormalizer->normalize($event),
                $metadata,
                $streamName,
                $this->streamVersions[(string)$streamName],
                $eventIdentifier ?? Uuid::uuid4()->toString(),
                $now
            );
            $this->streamVersions[(string)$streamName] ++;
            fwrite($this->eventFileResource, ExportedEvent::fromRawEvent($rawEvent, $attributes)->toJson() . chr(10));
        }
    }

}
