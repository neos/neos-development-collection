<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Projection;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\Neos\AssetUsage\Dto\AssetIdsByProperty;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\Neos\AssetUsage\Dto\NodeAddress;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\Media\Domain\Model\ResourceBasedInterface;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;
use Neos\Neos\AssetUsage\AssetUsageFinder;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Doctrine\DBAL\Connection;
use Neos\EventStore\CatchUp\CatchUp;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Neos\AssetUsage\Dto\AssetIdAndOriginalAssetId;

/**
 * @implements ProjectionInterface<AssetUsageFinder>
 * @internal
 */
final class AssetUsageProjection implements ProjectionInterface
{
    private ?AssetUsageFinder $stateAccessor = null;
    private AssetUsageRepository $repository;
    private DoctrineCheckpointStorage $checkpointStorage;
    /** @var array<string, string|null> */
    private array $originalAssetIdMappingRuntimeCache = [];

    public function __construct(
        private readonly EventNormalizer $eventNormalizer,
        private readonly AssetRepository $assetRepository,
        ContentRepositoryId $contentRepositoryId,
        Connection $dbal,
        AssetUsageRepositoryFactory $assetUsageRepositoryFactory,
    ) {
        $this->repository = $assetUsageRepositoryFactory->build($contentRepositoryId);
        $this->checkpointStorage = new DoctrineCheckpointStorage(
            $dbal,
            $this->repository->getTableNamePrefix() . '_checkpoint',
            self::class
        );
    }

    public function reset(): void
    {
        $this->repository->reset();
        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
    }

    public function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        try {
            $assetIdsByProperty = $this->getAssetIdsByProperty($event->initialPropertyValues);
        } catch (InvalidTypeException $e) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to extract asset ids from event "%s": %s',
                    $eventEnvelope->event->id->value,
                    $e->getMessage()
                ),
                1646321894,
                $e
            );
        }
        $nodeAddress = new NodeAddress(
            $event->getContentStreamId(),
            $event->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
            $event->getNodeAggregateId()
        );
        $this->repository->addUsagesForNode($nodeAddress, $assetIdsByProperty);
    }

    public function whenNodePropertiesWereSet(NodePropertiesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        try {
            $assetIdsByProperty = $this->getAssetIdsByProperty($event->propertyValues);
        } catch (InvalidTypeException $e) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to extract asset ids from event "%s": %s',
                    $eventEnvelope->event->id->value,
                    $e->getMessage()
                ),
                1646321894,
                $e
            );
        }
        $nodeAddress = new NodeAddress(
            $event->getContentStreamId(),
            $event->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
            $event->getNodeAggregateId()
        );
        $this->repository->addUsagesForNode($nodeAddress, $assetIdsByProperty);
    }

    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->repository->removeNode(
            $event->getNodeAggregateId(),
            $event->affectedOccupiedDimensionSpacePoints->toDimensionSpacePointSet()
        );
    }


    public function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $this->repository->copyDimensions($event->sourceOrigin, $event->peerOrigin);
    }

    public function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->repository->copyContentStream(
            $event->sourceContentStreamId,
            $event->newContentStreamId
        );
    }

    public function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->repository->removeContentStream($event->previousContentStreamId);
    }

    public function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        $this->repository->removeContentStream($event->previousContentStreamId);
    }

    public function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        $this->repository->removeContentStream($event->previousSourceContentStreamId);
    }

    public function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        $this->repository->removeContentStream($event->previousSourceContentStreamId);
    }

    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->repository->removeContentStream($event->previousContentStreamId);
    }

    public function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        $this->repository->removeContentStream($event->contentStreamId);
    }


    // ----------------

    /**
     * @throws InvalidTypeException
     */
    private function getAssetIdsByProperty(SerializedPropertyValues $propertyValues): AssetIdsByProperty
    {
        /** @var array<string, array<AssetIdAndOriginalAssetId>> $assetIds */
        $assetIds = [];
        /** @var SerializedPropertyValue|null $propertyValue */
        foreach ($propertyValues as $propertyName => $propertyValue) {
            // skip removed properties ({@see SerializedPropertyValues})
            if ($propertyValue === null) {
                continue;
            }
            $extractedAssetIds = $this->extractAssetIds(
                $propertyValue->type,
                $propertyValue->value,
            );

            $assetIds[$propertyName] = array_map(
                fn($assetId) => new AssetIdAndOriginalAssetId($assetId, $this->findOriginalAssetId($assetId)),
                $extractedAssetIds
            );
        }
        return new AssetIdsByProperty($assetIds);
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return array<string>
     * @throws InvalidTypeException
     */
    private function extractAssetIds(string $type, mixed $value): array
    {
        if ($type === 'string' || is_subclass_of($type, \Stringable::class, true)) {
            preg_match_all('/asset:\/\/(?<assetId>[\w-]*)/i', (string)$value, $matches, PREG_SET_ORDER);
            return array_map(static fn(array $match) => $match['assetId'], $matches);
        }
        if (is_subclass_of($type, ResourceBasedInterface::class, true)) {
            return isset($value['__identifier']) ? [$value['__identifier']] : [];
        }

        // Collection type?
        /** @var array{type: string, elementType: string|null, nullable: bool} $parsedType */
        $parsedType = TypeHandling::parseType($type);
        if ($parsedType['elementType'] === null) {
            return [];
        }
        if (
            !is_subclass_of($parsedType['elementType'], ResourceBasedInterface::class, true)
            && !is_subclass_of($parsedType['elementType'], \Stringable::class, true)
        ) {
            return [];
        }
        /** @var array<array<string>> $assetIds */
        $assetIds = [];
        /** @var iterable<mixed> $value */
        foreach ($value as $elementValue) {
            $assetIds[] = $this->extractAssetIds($parsedType['elementType'], $elementValue);
        }
        return array_merge(...$assetIds);
    }

    public function setUp(): void
    {
        $this->repository->setup();
        $this->checkpointStorage->setup();
    }

    public function canHandle(Event $event): bool
    {
        $eventClassName = $this->eventNormalizer->getEventClassName($event);
        return in_array($eventClassName, [
            NodeAggregateWithNodeWasCreated::class,
            NodePropertiesWereSet::class,
            NodeAggregateWasRemoved::class,
            NodePeerVariantWasCreated::class,
            ContentStreamWasForked::class,
            WorkspaceWasDiscarded::class,
            WorkspaceWasPartiallyDiscarded::class,
            WorkspaceWasPartiallyPublished::class,
            WorkspaceWasPublished::class,
            WorkspaceWasRebased::class,
            ContentStreamWasRemoved::class,
        ]);
    }

    public function catchUp(EventStreamInterface $eventStream, ContentRepository $contentRepository): void
    {
        $catchUp = CatchUp::create($this->apply(...), $this->checkpointStorage);
        $catchUp->run($eventStream);
    }

    private function apply(EventEnvelope $eventEnvelope): void
    {
        if (!$this->canHandle($eventEnvelope->event)) {
            return;
        }

        $eventInstance = $this->eventNormalizer->denormalize($eventEnvelope->event);

        match ($eventInstance::class) {
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($eventInstance, $eventEnvelope),
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($eventInstance, $eventEnvelope),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($eventInstance),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($eventInstance),
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($eventInstance),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($eventInstance),
            WorkspaceWasPartiallyDiscarded::class => $this->whenWorkspaceWasPartiallyDiscarded($eventInstance),
            WorkspaceWasPartiallyPublished::class => $this->whenWorkspaceWasPartiallyPublished($eventInstance),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($eventInstance),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($eventInstance),
            ContentStreamWasRemoved::class => $this->whenContentStreamWasRemoved($eventInstance),
            default => null,
        };
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->checkpointStorage->getHighestAppliedSequenceNumber();
    }

    public function getState(): AssetUsageFinder
    {
        if (!$this->stateAccessor) {
            $this->stateAccessor = new AssetUsageFinder($this->repository);
        }
        return $this->stateAccessor;
    }

    private function findOriginalAssetId(string $assetId): ?string
    {
        if (!array_key_exists($assetId, $this->originalAssetIdMappingRuntimeCache)) {
            /** @var AssetInterface|null $asset */
            $asset = $this->assetRepository->findByIdentifier($assetId);
            /** @phpstan-ignore-next-line  */
            $this->originalAssetIdMappingRuntimeCache[$assetId] = $asset instanceof AssetVariantInterface ? $asset->getOriginalAsset()->getIdentifier() : null;
        }

        return $this->originalAssetIdMappingRuntimeCache[$assetId];
    }
}
