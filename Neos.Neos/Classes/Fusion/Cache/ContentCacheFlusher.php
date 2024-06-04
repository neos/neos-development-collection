<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\GlobalAssetUsageService;
use Psr\Log\LoggerInterface;

/**
 * This service flushes Fusion content caches triggered by node changes.
 *
 * It is called when the projection changes: In this case, it is triggered by
 * {@see GraphProjectorCatchUpHookForCacheFlushing} which calls this method..
 *   This is the relevant case if publishing a workspace
 *   - where we f.e. need to flush the cache for Live.
 *
 */
#[Flow\Scope('singleton')]
class ContentCacheFlusher
{
    #[Flow\InjectConfiguration(path: "fusion.contentCacheDebugMode")]
    protected bool $debugMode;

    /**
     * @var array<string,string>
     */
    private array $tagsToFlushAfterPersistance = [];

    public function __construct(
        protected readonly ContentCache $contentCache,
        protected readonly LoggerInterface $systemLogger,
        protected readonly GlobalAssetUsageService $globalAssetUsageService,
        protected readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        protected readonly PersistenceManagerInterface $persistenceManager,
    ) {
    }

    /**
     * Main entry point to *directly* flush the caches of a given NodeAggregate
     */
    public function flushNodeAggregate(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId
    ): void {
        $tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        $tagsToFlush = array_merge(
            $this->collectTagsForChangeOnNodeAggregate($contentRepository, $workspaceName, $nodeAggregateId, false),
            $tagsToFlush
        );

        $this->flushTags($tagsToFlush);
    }

    /**
     * WorkspaceNameToFlush is nullable, so we can flush all workspaces as no specific workspaceName is provided.
     * This is needed to flush nodes on asset changes, as the asset can get rendered in all workspaces, but lives
     * usually only in live workspace.
     *
     * @return array<string,string>
     */
    private function collectTagsForChangeOnNodeAggregate(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
        bool $anyWorkspace,
    ): array {
        $contentGraph = $contentRepository->getContentGraph($workspaceName);

        $nodeAggregate = $contentGraph->findNodeAggregateById(
            $nodeAggregateId
        );
        if (!$nodeAggregate) {
            // Node Aggregate was removed in the meantime, so no need to clear caches on this one anymore.
            return [];
        }
        $workspaceNameToFlush = $anyWorkspace ? CacheTagWorkspaceName::ANY : $workspaceName;
        $tagsToFlush = $this->collectTagsForChangeOnNodeIdentifier($contentRepository->id, $workspaceNameToFlush, $nodeAggregateId);

        $tagsToFlush = array_merge($this->collectTagsForChangeOnNodeType(
            $nodeAggregate->nodeTypeName,
            $contentRepository->id,
            $workspaceNameToFlush,
            $nodeAggregateId,
            $contentRepository
        ), $tagsToFlush);

        $parentNodeAggregates = [];
        foreach (
            $contentGraph->findParentNodeAggregates(
                $nodeAggregateId
            ) as $parentNodeAggregate
        ) {
            $parentNodeAggregates[] = $parentNodeAggregate;
        }
        // we do not need these variables anymore here
        unset($nodeAggregateId);


        // NOTE: Normally, the content graph cannot contain cycles. However, during the
        // testcase "Features/ProjectionIntegrityViolationDetection/AllNodesAreConnectedToARootNodePerSubgraph.feature"
        // and in case of bugs, it could have actually cycles.
        // We still want the content cache flushing to work, without hanging up in an endless loop.
        // That's why we track the seen NodeAggregateIds to be sure we don't travers them multiple times.
        $processedNodeAggregateIds = [];

        while ($nodeAggregate = array_shift($parentNodeAggregates)) {
            assert($nodeAggregate instanceof NodeAggregate);
            if (isset($processedNodeAggregateIds[$nodeAggregate->nodeAggregateId->value])) {
                // we've already processed this NodeAggregateId (i.e. flushed the caches for it);
                // thus we can skip this one, and thus break the cycle.
                continue;
            }
            $processedNodeAggregateIds[$nodeAggregate->nodeAggregateId->value] = true;

            $tagName = CacheTag::forDescendantOfNode($contentRepository->id, $workspaceName, $nodeAggregate->nodeAggregateId);
            $tagsToFlush[$tagName->value] = sprintf(
                'which were tagged with "%s" because node "%s" has changed.',
                $tagName->value,
                $nodeAggregate->nodeAggregateId->value
            );

            foreach (
                $contentGraph->findParentNodeAggregates(
                    $nodeAggregate->nodeAggregateId
                ) as $parentNodeAggregate
            ) {
                $parentNodeAggregates[] = $parentNodeAggregate;
            }
        }

        return $tagsToFlush;
    }


    /**
     * @return array<string, string>
     */
    private function collectTagsForChangeOnNodeIdentifier(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName|CacheTagWorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
    ): array {
        $tagsToFlush = [];

        $nodeCacheIdentifier = CacheTag::forNodeAggregate($contentRepositoryId, $workspaceName, $nodeAggregateId);
        $tagsToFlush[$nodeCacheIdentifier->value] = sprintf(
            'which were tagged with "%s" because that identifier has changed.',
            $nodeCacheIdentifier->value
        );

        $dynamicNodeCacheIdentifier = CacheTag::forDynamicNodeAggregate($contentRepositoryId, $workspaceName, $nodeAggregateId);
        $tagsToFlush[$dynamicNodeCacheIdentifier->value] = sprintf(
            'which were tagged with "%s" because that identifier has changed.',
            $dynamicNodeCacheIdentifier->value
        );

        $descendantOfNodeCacheIdentifier = CacheTag::forDescendantOfNode($contentRepositoryId, $workspaceName, $nodeAggregateId);
        $tagsToFlush[$descendantOfNodeCacheIdentifier->value] = sprintf(
            'which were tagged with "%s" because node "%s" has changed.',
            $descendantOfNodeCacheIdentifier->value,
            $nodeCacheIdentifier->value
        );

        return $tagsToFlush;
    }

    /**
     * @return array<string,string> $tagsToFlush
     */
    private function collectTagsForChangeOnNodeType(
        NodeTypeName $nodeTypeName,
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName|CacheTagWorkspaceName $workspaceName,
        ?NodeAggregateId $referenceNodeIdentifier,
        ContentRepository $contentRepository
    ): array {
        $tagsToFlush = [];

        $nodeType = $contentRepository->getNodeTypeManager()->getNodeType($nodeTypeName);
        if ($nodeType) {
            $nodeTypesNamesToFlush = $this->getAllImplementedNodeTypeNames($nodeType);
        } else {
            // as a fallback, we flush the single NodeType
            $nodeTypesNamesToFlush = [$nodeTypeName->value];
        }

        foreach ($nodeTypesNamesToFlush as $nodeTypeNameToFlush) {
            $nodeTypeNameCacheIdentifier = CacheTag::forNodeTypeName($contentRepositoryId, $workspaceName, NodeTypeName::fromString($nodeTypeNameToFlush));
            $tagsToFlush[$nodeTypeNameCacheIdentifier->value] = sprintf(
                'which were tagged with "%s" because node "%s" has changed and was of type "%s".',
                $nodeTypeNameCacheIdentifier->value,
                ($referenceNodeIdentifier?->value ?? ''),
                $nodeTypeName->value
            );
        }

        return $tagsToFlush;
    }


    /**
     * Flush caches according to the given tags.
     *
     * @param array<string,string> $tagsToFlush
     */
    protected function flushTags(array $tagsToFlush): void
    {
        if ($this->debugMode) {
            foreach ($tagsToFlush as $tag => $logMessage) {
                $affectedEntries = $this->contentCache->flushByTag($tag);
                if ($affectedEntries > 0) {
                    $this->systemLogger->debug(sprintf(
                        'Content cache: Removed %s entries %s',
                        $affectedEntries,
                        $logMessage
                    ));
                }
            }
        } else {
            $affectedEntries = $this->contentCache->flushByTags(array_keys($tagsToFlush));
            $this->systemLogger->debug(sprintf('Content cache: Removed %s entries', $affectedEntries));
        }
    }

    /**
     * @param NodeType $nodeType
     * @return array<string>
     */
    protected function getAllImplementedNodeTypeNames(NodeType $nodeType): array
    {
        $self = $this;
        $types = array_reduce(
            $nodeType->getDeclaredSuperTypes(),
            function (array $types, NodeType $superType) use ($self) {
                return array_merge($types, $self->getAllImplementedNodeTypeNames($superType));
            },
            [$nodeType->name->value]
        );

        return array_unique($types);
    }


    /**
     * Fetches possible usages of the asset and registers nodes that use the asset as changed.
     *
     * @throws NodeTypeNotFound
     */
    public function registerAssetChange(AssetInterface $asset): void
    {
        // In Nodes only assets are referenced, never asset variants directly. When an asset
        // variant is updated, it is passed as $asset, but since it is never "used" by any node
        // no flushing of corresponding entries happens. Thus we instead use the original asset
        // of the variant.
        if ($asset instanceof AssetVariantInterface) {
            $asset = $asset->getOriginalAsset();
        }

        $tagsToFlush = [];
        $filter = AssetUsageFilter::create()
            ->withAsset($this->persistenceManager->getIdentifierByObject($asset))
            ->includeVariantsOfAsset();


        $workspaceNamesByContentStreamId = [];
        foreach ($this->globalAssetUsageService->findByFilter($filter) as $contentRepositoryId => $usages) {
            foreach ($usages as $usage) {
                // TODO: Remove when WorkspaceName is part of the AssetUsageProjection
                $workspaceName = $workspaceNamesByContentStreamId[$contentRepositoryId][$usage->contentStreamId->value] ?? null;
                if ($workspaceName === null) {
                    $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($contentRepositoryId));
                    $workspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId($usage->contentStreamId);
                    if ($workspace === null) {
                        continue;
                    }
                    $workspaceName = $workspace->workspaceName;
                    $workspaceNamesByContentStreamId[$contentRepositoryId][$usage->contentStreamId->value] = $workspaceName;
                }
                //

                $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($contentRepositoryId));
                $tagsToFlush = array_merge(
                    $this->collectTagsForChangeOnNodeAggregate(
                        $contentRepository,
                        $workspaceName,
                        $usage->nodeAggregateId,
                        true
                    ),
                    $tagsToFlush
                );
            }
        }

        $this->tagsToFlushAfterPersistance = array_merge($tagsToFlush, $this->tagsToFlushAfterPersistance);
    }

    /**
     * Flush caches according to the previously registered changes.
     */
    public function flushCollectedTags(): void
    {
        $this->flushTags($this->tagsToFlushAfterPersistance);
        $this->tagsToFlushAfterPersistance = [];
    }

    public function shutdownObject(): void
    {
        $this->flushCollectedTags();
    }
}
