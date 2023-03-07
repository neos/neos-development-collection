<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Projector;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ESCR\AssetUsage\Dto\AssetIdsByProperty;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\Media\Domain\Model\ResourceBasedInterface;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;

// NOTE: as workaround, we cannot reflect this class (because of an overly eager DefaultEventToListenerMappingProvider in
// Neos.EventSourcing - which will be refactored soon). That's why we need an extra factory for this class.
// See Neos.ContentRepositoryRegistry/Configuration/Settings.hacks.yaml for further details.
final class AssetUsageProjection implements ProjectionInterface // TODO IMPLEMENT
{

    public function __construct(
        private readonly AssetUsageRepository $repository
    ) {
    }

    public function reset(): void
    {
        $this->repository->reset();
        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
    }

    public function whenNodeAggregateWithNodeWasCreated(
        NodeAggregateWithNodeWasCreated $event,
        RawEvent $rawEvent
    ): void {
        try {
            $assetIdsByProperty = $this->getAssetIdsByProperty($event->initialPropertyValues);
        } catch (InvalidTypeException $e) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to extract asset ids from event "%s": %s',
                    $rawEvent->getIdentifier(),
                    $e->getMessage()
                ),
                1646321894,
                $e
            );
        }
        $nodeAddress = new NodeAddress(
            $event->contentStreamId,
            $event->originDimensionSpacePoint->toDimensionSpacePoint(),
            $event->nodeAggregateId,
            null
        );
        $this->repository->addUsagesForNode($nodeAddress, $assetIdsByProperty);
    }

    public function whenNodePropertiesWereSet(NodePropertiesWereSet $event, RawEvent $rawEvent): void
    {
        try {
            $assetIdsByProperty = $this->getAssetIdsByProperty($event->propertyValues);
        } catch (InvalidTypeException $e) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to extract asset ids from event "%s": %s',
                    $rawEvent->getIdentifier(),
                    $e->getMessage()
                ),
                1646321894,
                $e
            );
        }
        $nodeAddress = new NodeAddress(
            $event->getContentStreamId(),
            $event->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
            $event->getNodeAggregateId(),
            null
        );
        $this->repository->addUsagesForNode($nodeAddress, $assetIdsByProperty);
    }

    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->repository->removeNode(
            $event->getNodeAggregateId(),
            $event->getAffectedOccupiedDimensionSpacePoints()->toDimensionSpacePointSet()
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
        /** @var array<string, array<string>> $assetIdentifiers */
        $assetIdentifiers = [];
        /** @var \Neos\ContentRepository\Core\Feature\NodeModification\SerializedPropertyValue $propertyValue */
        foreach ($propertyValues as $propertyName => $propertyValue) {
            $assetIdentifiers[$propertyName] = $this->extractAssetIdentifiers(
                $propertyValue->getType(),
                $propertyValue->getValue()
            );
        }
        return new AssetIdsByProperty($assetIdentifiers);
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return array<string>
     * @throws InvalidTypeException
     */
    private function extractAssetIdentifiers(string $type, mixed $value): array
    {
        if ($type === 'string' || is_subclass_of($type, \Stringable::class, true)) {
            // @phpstan-ignore-next-line
            preg_match_all('/asset:\/\/(?<assetId>[\w-]*)/i', (string)$value, $matches, PREG_SET_ORDER);
            return array_map(static fn(array $match) => $match['assetId'], $matches);
        }
        if (is_subclass_of($type, ResourceBasedInterface::class, true)) {
            // @phpstan-ignore-next-line
            return isset($value['__identifier']) ? [$value['__identifier']] : [];
        }

        // Collection type?
        /** @var array{type: string, elementType: string|null, nullable: bool} $parsedType */
        $parsedType = TypeHandling::parseType($type);
        if ($parsedType['elementType'] === null) {
            return [];
        }
        if (!is_subclass_of($parsedType['elementType'], ResourceBasedInterface::class, true)
            && !is_subclass_of($parsedType['elementType'], \Stringable::class, true)) {
            return [];
        }
        /** @var array<array<string>> $assetIdentifiers */
        $assetIdentifiers = [];
        /** @var iterable<mixed> $value */
        foreach ($value as $elementValue) {
            $assetIdentifiers[] = $this->extractAssetIdentifiers($parsedType['elementType'], $elementValue);
        }
        return array_merge(...$assetIdentifiers);
    }

    public function setUp(): void
    {
        // TODO: Implement setUp() method.
    }

    public function canHandle(Event $event): bool
    {
        // TODO: Implement canHandle() method.
    }

    public function catchUp(EventStreamInterface $eventStream, ContentRepository $contentRepository): void
    {
        // TODO: Implement catchUp() method.
    }

    public function getSequenceNumber(): SequenceNumber
    {
        // TODO: Implement getSequenceNumber() method.
    }

    public function getState(): ProjectionStateInterface
    {
        // TODO: Implement getState() method.
    }
}