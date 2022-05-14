<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Projector;

use Neos\ESCR\AssetUsage\Dto\AssetIdsByProperty;
use Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValue;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Media\Domain\Model\ResourceBasedInterface;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;

// NOTE: as workaround, we cannot reflect this class (because of an overly eager DefaultEventToListenerMappingProvider in
// Neos.EventSourcing - which will be refactored soon). That's why we need an extra factory for this class.
// See Neos.ContentRepositoryRegistry/Configuration/Settings.hacks.yaml for further details.
final class AssetUsageProjector implements ProjectorInterface
{

    public function __construct(
        private readonly AssetUsageRepository $repository
    ) {
    }

    public function reset(): void
    {
        $this->repository->reset();
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
            $event->contentStreamIdentifier,
            $event->originDimensionSpacePoint->toDimensionSpacePoint(),
            $event->nodeAggregateIdentifier,
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
            $event->getContentStreamIdentifier(),
            $event->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
            $event->getNodeAggregateIdentifier(),
            null
        );
        $this->repository->addUsagesForNode($nodeAddress, $assetIdsByProperty);
    }

    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->repository->removeNode(
            $event->getNodeAggregateIdentifier(),
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
            $event->getSourceContentStreamIdentifier(),
            $event->getContentStreamIdentifier()
        );
    }

    public function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->repository->removeContentStream($event->getPreviousContentStreamIdentifier());
    }

    public function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        $this->repository->removeContentStream($event->getPreviousContentStreamIdentifier());
    }

    public function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        $this->repository->removeContentStream($event->getPreviousSourceContentStreamIdentifier());
    }

    public function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        $this->repository->removeContentStream($event->getPreviousSourceContentStreamIdentifier());
    }

    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->repository->removeContentStream($event->getPreviousContentStreamIdentifier());
    }

    public function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        $this->repository->removeContentStream($event->getContentStreamIdentifier());
    }


    // ----------------

    /**
     * @throws InvalidTypeException
     */
    private function getAssetIdsByProperty(SerializedPropertyValues $propertyValues): AssetIdsByProperty
    {
        /** @var array<string, array<string>> $assetIdentifiers */
        $assetIdentifiers = [];
        /** @var SerializedPropertyValue $propertyValue */
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
}
