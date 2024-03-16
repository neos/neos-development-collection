<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Exception\ORMException;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Infrastructure\DbalCheckpointStorage;
use Neos\ContentRepository\Core\Projection\CheckpointStorageStatusType;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\ResourceBasedInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\Dto\AssetIdAndOriginalAssetId;
use Neos\Neos\AssetUsage\Dto\AssetIdsByProperty;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsageNodeAddress;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;

/**
 * @implements ProjectionInterface<AssetUsageFinder>
 * @internal
 */
final class AssetUsageProjection implements ProjectionInterface
{
    private ?AssetUsageFinder $stateAccessor = null;
    private AssetUsageRepository $repository;
    private DbalCheckpointStorage $checkpointStorage;
    /** @var array<string, string|null> */
    private array $originalAssetIdMappingRuntimeCache = [];

    public function __construct(
        private readonly AssetRepository $assetRepository,
        ContentRepositoryId $contentRepositoryId,
        Connection $dbal,
        AssetUsageRepositoryFactory $assetUsageRepositoryFactory,
    ) {
        $this->repository = $assetUsageRepositoryFactory->build($contentRepositoryId);
        $this->checkpointStorage = new DbalCheckpointStorage(
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
        $nodeAddress = new AssetUsageNodeAddress(
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
        $nodeAddress = new AssetUsageNodeAddress(
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
        foreach ($propertyValues as $propertyName => $propertyValue) {
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
     * @param mixed $value
     * @return array<string>
     * @throws InvalidTypeException
     */
    private function extractAssetIds(string $type, mixed $value): array
    {
        if (is_string($value)) {
            preg_match_all('/asset:\/\/(?<assetId>[\w-]*)/i', $value, $matches, PREG_SET_ORDER);
            return array_map(static fn(array $match) => $match['assetId'], $matches);
        }
        if (is_subclass_of($type, ResourceBasedInterface::class)) {
            return isset($value['__identifier']) ? [$value['__identifier']] : [];
        }

        // Collection type?
        /** @var array{type: string, elementType: string|null, nullable: bool} $parsedType */
        $parsedType = TypeHandling::parseType($type);
        if ($parsedType['elementType'] === null) {
            return [];
        }
        if (
            !is_subclass_of($parsedType['elementType'], ResourceBasedInterface::class)
            && !is_subclass_of($parsedType['elementType'], \Stringable::class)
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
        $this->repository->setUp();
        $this->checkpointStorage->setUp();
    }

    public function status(): ProjectionStatus
    {
        $checkpointStorageStatus = $this->checkpointStorage->status();
        if ($checkpointStorageStatus->type === CheckpointStorageStatusType::ERROR) {
            return ProjectionStatus::error($checkpointStorageStatus->details);
        }
        if ($checkpointStorageStatus->type === CheckpointStorageStatusType::SETUP_REQUIRED) {
            return ProjectionStatus::setupRequired($checkpointStorageStatus->details);
        }
        try {
            $falseOrDetailsString = $this->repository->isSetupRequired();
            if (is_string($falseOrDetailsString)) {
                return ProjectionStatus::setupRequired($falseOrDetailsString);
            }
        } catch (\Throwable $e) {
            return ProjectionStatus::error(sprintf('Failed to determine required SQL statements: %s', $e->getMessage()));
        }
        return ProjectionStatus::ok();
    }

    public function canHandle(EventInterface $event): bool
    {
        return in_array($event::class, [
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

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($event, $eventEnvelope),
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($event, $eventEnvelope),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($event),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($event),
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($event),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($event),
            WorkspaceWasPartiallyDiscarded::class => $this->whenWorkspaceWasPartiallyDiscarded($event),
            WorkspaceWasPartiallyPublished::class => $this->whenWorkspaceWasPartiallyPublished($event),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($event),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($event),
            ContentStreamWasRemoved::class => $this->whenContentStreamWasRemoved($event),
            default => throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
        };
    }

    public function getCheckpointStorage(): DbalCheckpointStorage
    {
        return $this->checkpointStorage;
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
            try {
                /** @var AssetInterface|null $asset */
                $asset = $this->assetRepository->findByIdentifier($assetId);
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ORMException) {
                return null;
            }
            /** @phpstan-ignore-next-line  */
            $this->originalAssetIdMappingRuntimeCache[$assetId] = $asset instanceof AssetVariantInterface ? $asset->getOriginalAsset()->getIdentifier() : null;
        }

        return $this->originalAssetIdMappingRuntimeCache[$assetId];
    }
}
