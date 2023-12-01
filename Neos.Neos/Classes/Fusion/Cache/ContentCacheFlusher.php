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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Psr\Log\LoggerInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\AssetUsage\Dto\AssetUsageReference;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

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

    public function __construct(
        protected readonly ContentCache $contentCache,
        protected readonly LoggerInterface $systemLogger,
        protected readonly AssetService $assetService,
        protected readonly PersistenceManagerInterface $persistenceManager,
        protected readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {
    }

    /**
     * Main entry point to *directly* flush the caches of a given NodeAggregate
     *
     * @param ContentRepository $contentRepository
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $nodeAggregateId
     */
    public function flushNodeAggregate(
        ContentRepository $contentRepository,
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): void {
        $tagsToFlush = [];

        $tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        $this->registerChangeOnNodeIdentifier($contentRepository->id, $contentStreamId, $nodeAggregateId, $tagsToFlush);
        $nodeAggregate = $contentRepository->getContentGraph()->findNodeAggregateById(
            $contentStreamId,
            $nodeAggregateId
        );
        if (!$nodeAggregate) {
            // Node Aggregate was removed in the meantime, so no need to clear caches on this one anymore.
            return;
        }

        $this->registerChangeOnNodeType(
            $nodeAggregate->nodeTypeName,
            $contentRepository->id,
            $contentStreamId,
            $nodeAggregateId,
            $tagsToFlush,
            $contentRepository
        );

        $parentNodeAggregates = [];
        foreach (
            $contentRepository->getContentGraph()->findParentNodeAggregates(
                $contentStreamId,
                $nodeAggregateId
            ) as $parentNodeAggregate
        ) {
            $parentNodeAggregates[] = $parentNodeAggregate;
        }
        // we do not need these variables anymore here
        unset($contentStreamId, $nodeAggregateId);


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

            $tagName = CacheTag::forDescendantOfNode($contentRepository->id, $nodeAggregate->contentStreamId, $nodeAggregate->nodeAggregateId);
            $tagsToFlush[$tagName->value] = sprintf(
                'which were tagged with "%s" because node "%s" has changed.',
                $tagName->value,
                $nodeAggregate->nodeAggregateId->value
            );

            foreach (
                $contentRepository->getContentGraph()->findParentNodeAggregates(
                    $nodeAggregate->contentStreamId,
                    $nodeAggregate->nodeAggregateId
                ) as $parentNodeAggregate
            ) {
                $parentNodeAggregates[] = $parentNodeAggregate;
            }
        }
        $this->flushTags($tagsToFlush);
    }


    /**
     * Please use registerNodeChange() if possible. This method is a low-level api. If you do use this method make sure
     * that $cacheIdentifier contains the workspacehash as well as the node identifier:
     * $workspaceHash .'_'. $nodeIdentifier
     * The workspacehash can be received via $this->getCachingHelper()->renderWorkspaceTagForContextNode($workpsacename)
     *
     * @param array<string,string> &$tagsToFlush
     */
    private function registerChangeOnNodeIdentifier(
        ContentRepositoryId $contentRepositoryId,
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        array &$tagsToFlush
    ): void {

        $nodeCacheIdentifier = CacheTag::forNodeAggregate($contentRepositoryId, $contentStreamId, $nodeAggregateId);
        $tagsToFlush[$nodeCacheIdentifier->value] = sprintf(
            'which were tagged with "%s" because that identifier has changed.',
            $nodeCacheIdentifier->value
        );

        $descandantOfNodeCacheIdentifier = CacheTag::forDescendantOfNode($contentRepositoryId, $contentStreamId, $nodeAggregateId);
        $tagsToFlush[$descandantOfNodeCacheIdentifier->value] = sprintf(
            'which were tagged with "%s" because node "%s" has changed.',
            $descandantOfNodeCacheIdentifier->value,
            $nodeCacheIdentifier->value
        );
    }

    /**
     * This is a low-level api. Please use registerNodeChange() if possible. Otherwise make sure that $nodeTypePrefix
     * is set up correctly and contains the workspacehash wich can be received via
     * $this->getCachingHelper()->renderWorkspaceTagForContextNode($workpsacename)
     *
     * @param array<string,string> &$tagsToFlush
     */
    private function registerChangeOnNodeType(
        NodeTypeName $nodeTypeName,
        ContentRepositoryId $contentRepositoryId,
        ContentStreamId $contentStreamId,
        ?NodeAggregateId $referenceNodeIdentifier,
        array &$tagsToFlush,
        ContentRepository $contentRepository
    ): void {
        try {
            $nodeTypesNamesToFlush = $this->getAllImplementedNodeTypeNames(
                $contentRepository->getNodeTypeManager()->getNodeType($nodeTypeName)
            );
        } catch (NodeTypeNotFoundException $e) {
            // as a fallback, we flush the single NodeType
            $nodeTypesNamesToFlush = [$nodeTypeName->value];
        }

        foreach ($nodeTypesNamesToFlush as $nodeTypeNameToFlush) {
            $nodeTypeNameCacheIdentifier = CacheTag::forNodeTypeName($contentRepositoryId, $contentStreamId, NodeTypeName::fromString($nodeTypeNameToFlush));
            $tagsToFlush[$nodeTypeNameCacheIdentifier->value] = sprintf(
                'which were tagged with "%s" because node "%s" has changed and was of type "%s".',
                $nodeTypeNameCacheIdentifier->value,
                ($referenceNodeIdentifier?->value ?? ''),
                $nodeTypeName->value
            );
        }
    }


    /**
     * Flush caches according to the previously registered node changes.
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
     * @throws NodeTypeNotFoundException
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

        if (!$this->assetService->isInUse($asset)) {
            return;
        }

        $tagsToFlush = [];
        foreach ($this->assetService->getUsageReferences($asset) as $reference) {
            if (!$reference instanceof AssetUsageReference) {
                continue;
            }
            $contentRepository = $this->contentRepositoryRegistry->get($reference->getContentRepositoryId());
            $this->flushNodeAggregate($contentRepository, $reference->getContentStreamId(), $reference->getNodeAggregateId());
        }
        $this->flushTags($tagsToFlush);
    }
}
