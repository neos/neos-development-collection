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

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Fusion\Core\Cache\ContentCache;
use Psr\Log\LoggerInterface;

/**
 * This service flushes Fusion content caches triggered by node changes.
 *
 * It is called when the projection changes: In this case, it is triggered by
 * {@see GraphProjectorCatchUpHookForCacheFlushing} which calls this method.
 *   This is the relevant case if publishing a workspace
 *   - where we f.e. need to flush the cache for Live.
 *
 * The {@see AssetChangeHandlerForCacheFlushing} also calls this ContentCacheFlusher
 * to flush the caches of all Nodes using a given asset that has changed.
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
        protected readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        protected readonly PersistenceManagerInterface $persistenceManager,
    ) {
    }

    /**
     * Main entry point to flush the caches of a given workspaceName with a given strategy.
     */
    public function flushWorkspace(
        FlushWorkspaceRequest $flushWorkspaceRequest,
        CacheFlushingStrategy $cacheFlushingStrategy
    ): void {
        $tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        $nodeCacheIdentifier = CacheTag::forWorkspaceName($flushWorkspaceRequest->contentRepositoryId, $flushWorkspaceRequest->workspaceName);
        $tagsToFlush[$nodeCacheIdentifier->value] = sprintf(
            'which were tagged with "%s" because that identifier has changed.',
            $nodeCacheIdentifier->value
        );

        $this->flushTags($tagsToFlush, $cacheFlushingStrategy);
    }

    /**
     * Main entry point to flush the caches of a given NodeAggregate with a given strategy.
     */
    public function flushNodeAggregate(
        FlushNodeAggregateRequest $flushNodeAggregateRequest,
        CacheFlushingStrategy $cacheFlushingStrategy
    ): void {
        $tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        $tagsToFlush = array_merge(
            $this->collectTagsForChangeOnNodeAggregate($flushNodeAggregateRequest, false),
            $tagsToFlush
        );

        $this->flushTags($tagsToFlush, $cacheFlushingStrategy);
    }

    /**
     * @param bool $anyWorkspace This is needed to flush nodes on asset changes, as the asset can get rendered in all workspaces, but lives
     *                            usually only in live workspace.
     *
     * @return array<string,string>
     */
    private function collectTagsForChangeOnNodeAggregate(
        FlushNodeAggregateRequest $flushNodeAggregateRequest,
        bool $anyWorkspace,
    ): array {
        $workspaceNameToFlush = $anyWorkspace ? CacheTagWorkspaceName::ANY : $flushNodeAggregateRequest->workspaceName;
        $tagsToFlush = $this->collectTagsForChangeOnNodeIdentifier($flushNodeAggregateRequest->contentRepositoryId, $workspaceNameToFlush, $flushNodeAggregateRequest->nodeAggregateId);

        $tagsToFlush = array_merge($this->collectTagsForChangeOnNodeType(
            $flushNodeAggregateRequest->nodeTypeName,
            $flushNodeAggregateRequest->contentRepositoryId,
            $workspaceNameToFlush,
            $flushNodeAggregateRequest->nodeAggregateId,
        ), $tagsToFlush);

        $parentNodeAggregateIds = $flushNodeAggregateRequest->parentNodeAggregateIds;
        foreach ($parentNodeAggregateIds as $parentNodeAggregateId) {
            $tagName = CacheTag::forDescendantOfNode($flushNodeAggregateRequest->contentRepositoryId, $workspaceNameToFlush, $parentNodeAggregateId);
            $tagsToFlush[$tagName->value] = sprintf(
                'which were tagged with "%s" because node "%s" has changed.',
                $tagName->value,
                $parentNodeAggregateId->value
            );
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
        ?NodeAggregateId $referenceNodeIdentifier
    ): array {
        $tagsToFlush = [];

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
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
     * Flush caches according to the given tags and strategy.
     *
     * @param array<string,string> $tagsToFlush
     */
    protected function flushTags(array $tagsToFlush, CacheFlushingStrategy $cacheFlushingStrategy): void
    {
        match ($cacheFlushingStrategy) {
            CacheFlushingStrategy::IMMEDIATELY => $this->flushTagsImmediately($tagsToFlush),
            CacheFlushingStrategy::ON_SHUTDOWN => $this->collectTagsForFlushOnShutdown($tagsToFlush)
        };
    }


    /**
     * Flush caches according to the given tags immediately.
     *
     * @param array<string,string> $tagsToFlush
     */
    protected function flushTagsImmediately(array $tagsToFlush): void
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
     * Collect tags to get flushed on shutdown.
     *
     * @param array<string,string> $tagsToFlush
     */
    protected function collectTagsForFlushOnShutdown(array $tagsToFlush): void
    {
        $this->tagsToFlushAfterPersistance = array_merge($tagsToFlush, $this->tagsToFlushAfterPersistance);
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
     * Flush caches according to the previously registered changes.
     */
    public function flushCollectedTags(): void
    {
        $this->flushTagsImmediately($this->tagsToFlushAfterPersistance);
        $this->tagsToFlushAfterPersistance = [];
    }

    public function shutdownObject(): void
    {
        $this->flushCollectedTags();
    }
}
