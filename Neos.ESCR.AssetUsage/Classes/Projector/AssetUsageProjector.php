<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Projector;

use Neos\ESCR\AssetUsage\Dto\AssetIdsByProperty;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePeerVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePropertiesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasDiscarded;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasPartiallyDiscarded;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasPartiallyPublished;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasPublished;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasRebased;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ResourceBasedInterface;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;

/**
 * @Flow\Scope("singleton")
 */
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
            $assetIdsByProperty = $this->getAssetIdsByProperty($event->getInitialPropertyValues());
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
