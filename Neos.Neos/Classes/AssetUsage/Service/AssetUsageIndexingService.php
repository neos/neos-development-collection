<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Service;

use Doctrine\ORM\ORMException;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\ResourceBasedInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\Domain\AssetUsageRepository;
use Neos\Neos\AssetUsage\Dto\AssetIdAndOriginalAssetId;
use Neos\Neos\AssetUsage\Dto\AssetIdsByProperty;
use Neos\Utility\TypeHandling;

class AssetUsageIndexingService
{
    /** @var array <string, string> */
    private array $originalAssetIdMappingRuntimeCache = [];

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly AssetUsageRepository $assetUsageRepository,
        private readonly AssetRepository $assetRepository,
        private readonly PersistenceManager $persistenceManager,
    ) {
    }

    /** @var array<string, array<string, WorkspaceName[]>> */
    private array $workspaceBases = [];

    /** @var array<string, array<string, WorkspaceName[]>> */
    private array $workspaceDependents = [];

    public function updateIndex(ContentRepositoryId $contentRepositoryId, Node $node): void
    {
        $workspaceBases = $this->getWorkspaceBasesAndWorkspace($contentRepositoryId, $node->workspaceName);
        $workspaceDependents = $this->getWorkspaceDependents($contentRepositoryId, $node->workspaceName);
        $nodeType = $this->contentRepositoryRegistry->get($contentRepositoryId)->getNodeTypeManager()->getNodeType($node->nodeTypeName);

        if ($nodeType === null) {
            return;
        }

        $assetIdsByPropertyOfNode = $this->getAssetIdsByProperty($nodeType, $node->properties);
        $assetUsagesInAncestorWorkspaces = $this->assetUsageRepository->findUsageForNodeInWorkspaces($contentRepositoryId, $node, $workspaceBases);

        $propertiesAndAssetIdsNotExistingInAncestors = [];
        foreach ($assetIdsByPropertyOfNode as $propertyName => $assetIdAndOriginalAssetIds) {
            foreach ($assetIdAndOriginalAssetIds as $assetIdAndOriginalAssetId) {
                foreach ($assetUsagesInAncestorWorkspaces as $assetUsage) {
                    if (
                        $assetUsage->assetId === $assetIdAndOriginalAssetId->assetId
                        && $assetUsage->propertyName === $propertyName
                    ) {
                        continue 2;
                    }
                }
                $propertiesAndAssetIdsNotExistingInAncestors[$propertyName][] = $assetIdAndOriginalAssetId;
            }
        }
        $assetIdsByPropertyNotExistingInAncestors = new AssetIdsByProperty($propertiesAndAssetIdsNotExistingInAncestors);

        $removedPropertiesAndAssetIds = [];
        foreach ($assetUsagesInAncestorWorkspaces as $assetUsage) {
            $assetUsageFound = false;
            $assetIds = [];
            foreach ($assetIdsByPropertyOfNode as $property => $assetIdAndOriginalAssetIds) {
                if ($assetUsage->propertyName === $property) {
                    foreach ($assetIdAndOriginalAssetIds as $assetIdAndOriginalAssetId) {
                        if (
                            $assetUsage->assetId === $assetIdAndOriginalAssetId->assetId
                        ) {
                            $assetUsageFound = true;
                            continue 2;
                        }
                    }
                    $assetIds[] = $assetUsage->assetId;
                }
            }
            if (!$assetUsageFound) {
                $assetIds[] = $assetUsage->assetId;
            }
            $removedPropertiesAndAssetIds[$assetUsage->propertyName] = array_map(
                fn ($removedAssetIds) => new AssetIdAndOriginalAssetId($removedAssetIds, $this->findOriginalAssetId($removedAssetIds)),
                $assetIds
            );
        }
        $removedAssetIdsByProperty = new AssetIdsByProperty($removedPropertiesAndAssetIds);


        // TODO: TEST something is changed in child workspace ... and afterwards changed in a workspace between

        foreach ($assetIdsByPropertyNotExistingInAncestors as $propertyName => $assetIdAndOriginalAssetIds) {
            /** @var AssetIdAndOriginalAssetId $assetIdAndOriginalAssetId */
            foreach ($assetIdAndOriginalAssetIds as $assetIdAndOriginalAssetId) {
                $this->assetUsageRepository->addUsagesForNodeWithAssetOnProperty($contentRepositoryId, $node, $propertyName, $assetIdAndOriginalAssetId->assetId, $assetIdAndOriginalAssetId->originalAssetId);
                $this->assetUsageRepository->removeAssetUsagesForNodeAggregateIdAndDimensionSpacePointWithAssetOnPropertyInWorkspaces(
                    $contentRepositoryId,
                    $node->aggregateId,
                    $node->dimensionSpacePoint,
                    $propertyName,
                    $assetIdAndOriginalAssetId->assetId,
                    $workspaceDependents
                );
            }
        }
        foreach ($removedAssetIdsByProperty as $propertyName => $assetIdAndOriginalAssetIds) {
            /** @var AssetIdAndOriginalAssetId $assetIdAndOriginalAssetId */
            foreach ($assetIdAndOriginalAssetIds as $assetIdAndOriginalAssetId) {
                $this->assetUsageRepository->removeAssetUsagesForNodeAggregateIdAndDimensionSpacePointWithAssetOnPropertyInWorkspaces(
                    $contentRepositoryId,
                    $node->aggregateId,
                    $node->dimensionSpacePoint,
                    $propertyName,
                    $assetIdAndOriginalAssetId->assetId,
                    [$node->workspaceName]
                );
            }
        }
    }

    public function updateDimensionSpacePointInIndex(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, DimensionSpacePoint $source, DimensionSpacePoint $target): void
    {
        $this->assetUsageRepository->updateAssetUsageDimensionSpacePoint($contentRepositoryId, $workspaceName, $source, $target);
    }

    public function removeIndexForWorkspaceNameNodeAggregateIdAndDimensionSpacePoint(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        $this->assetUsageRepository->removeAssetUsagesOfWorkspaceWithAllProperties(
            $contentRepositoryId,
            $workspaceName,
            $nodeAggregateId,
            $dimensionSpacePoint
        );
    }

    public function removeIndexForNode(
        ContentRepositoryId $contentRepositoryId,
        Node $node
    ): void {
        $this->removeIndexForWorkspaceNameNodeAggregateIdAndDimensionSpacePoint(
            $contentRepositoryId,
            $node->workspaceName,
            $node->aggregateId,
            $node->dimensionSpacePoint
        );
    }

    public function removeIndexForWorkspace(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName
    ): void {
        $this->assetUsageRepository->removeAssetUsagesOfWorkspace($contentRepositoryId, $workspaceName);
    }

    public function pruneIndex(ContentRepositoryId $contentRepositoryId): void
    {
        $this->assetUsageRepository->removeAll($contentRepositoryId);
    }

    /**
     * @return WorkspaceName[]
     */
    private function getWorkspaceBasesAndWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): array
    {
        if (!isset($this->workspaceBases[$contentRepositoryId->value][$workspaceName->value])) {
            $workspaceFinder = $this->contentRepositoryRegistry->get($contentRepositoryId)->getWorkspaceFinder();
            $workspace = $workspaceFinder->findOneByName($workspaceName);
            if ($workspace === null) {
                throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
            }

            $stack = [$workspace];

            $collectedWorkspaceNames = [$workspaceName];

            while ($stack !== []) {
                $workspace = array_shift($stack);
                if ($workspace->baseWorkspaceName) {
                    $ancestor = $workspaceFinder->findOneByName($workspace->baseWorkspaceName);
                    if ($ancestor === null) {
                        throw WorkspaceDoesNotExist::butWasSupposedTo($workspace->baseWorkspaceName);
                    }
                    $stack[] = $ancestor;
                    $collectedWorkspaceNames[] = $ancestor->workspaceName;
                }
            }

            $this->workspaceBases[$contentRepositoryId->value][$workspaceName->value] = $collectedWorkspaceNames;
        }

        return $this->workspaceBases[$contentRepositoryId->value][$workspaceName->value];
    }

    /**
     * @return WorkspaceName[]
     */
    private function getWorkspaceDependents(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): array
    {
        if (!isset($this->workspaceDependents[$contentRepositoryId->value][$workspaceName->value])) {
            $workspaceFinder = $this->contentRepositoryRegistry->get($contentRepositoryId)->getWorkspaceFinder();
            $workspace = $workspaceFinder->findOneByName($workspaceName);
            if ($workspace === null) {
                throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
            }
            $stack = [$workspace];
            $collectedWorkspaceNames = [];

            while ($stack !== []) {
                /** @var Workspace $workspace */
                $workspace = array_shift($stack);
                $descendants = $workspaceFinder->findByBaseWorkspace($workspace->workspaceName);
                foreach ($descendants as $descendant) {
                    $collectedWorkspaceNames[] = $descendant->workspaceName;
                    $stack[] = $descendant;
                }
            }
            $this->workspaceDependents[$contentRepositoryId->value][$workspaceName->value] = $collectedWorkspaceNames;
        }

        return $this->workspaceDependents[$contentRepositoryId->value][$workspaceName->value];
    }

    private function getAssetIdsByProperty(NodeType $nodeType, PropertyCollection $propertyValues): AssetIdsByProperty
    {
        /** @var array<string, array<AssetIdAndOriginalAssetId>> $assetIds */
        $assetIds = [];
        foreach ($propertyValues->serialized() as $propertyName => $propertyValue) {
            if (!$nodeType->hasProperty($propertyName)) {
                continue;
            }
            $propertyType = $nodeType->getPropertyType($propertyName);

            try {
                $extractedAssetIds = $this->extractAssetIds(
                    $propertyType,
                    $propertyValues->offsetGet($propertyName instanceof PropertyName ? $propertyName->value : $propertyName),
                );
            } catch (\Exception) {
                $extractedAssetIds = [];
                // We can't deserialize the property, so skip.
            }

            $assetIds[$propertyName] = array_map(
                fn ($assetId) => new AssetIdAndOriginalAssetId($assetId, $this->findOriginalAssetId($assetId)),
                $extractedAssetIds
            );
        }
        return new AssetIdsByProperty($assetIds);
    }

    /**
     * @return array<string>
     */
    private function extractAssetIds(string $type, mixed $value): array
    {
        if (is_string($value)) {
            preg_match_all('/asset:\/\/(?<assetId>[\w-]*)/i', $value, $matches, PREG_SET_ORDER);
            return array_map(static fn (array $match) => $match['assetId'], $matches);
        }
        if (is_subclass_of($type, ResourceBasedInterface::class)) {
            return [$this->persistenceManager->getIdentifierByObject($value)];
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

    private function findOriginalAssetId(string $assetId): ?string
    {
        if (!array_key_exists($assetId, $this->originalAssetIdMappingRuntimeCache)) {
            try {
                /** @var AssetInterface|null $asset */
                $asset = $this->assetRepository->findByIdentifier($assetId);
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ORMException) {
                return null;
            }
            /** @phpstan-ignore-next-line */
            $this->originalAssetIdMappingRuntimeCache[$assetId] = $asset instanceof AssetVariantInterface ? $asset->getOriginalAsset()->getIdentifier() : null;
        }

        return $this->originalAssetIdMappingRuntimeCache[$assetId];
    }
}
