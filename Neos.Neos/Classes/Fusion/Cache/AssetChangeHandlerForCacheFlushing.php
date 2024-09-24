<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\GlobalAssetUsageService;

class AssetChangeHandlerForCacheFlushing
{
    public function __construct(
        protected readonly GlobalAssetUsageService $globalAssetUsageService,
        protected readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        protected readonly PersistenceManagerInterface $persistenceManager,
        protected readonly ContentCacheFlusher $contentCacheFlusher,
    ) {
    }

    /**
     * Fetches possible usages of the asset and registers nodes that use the asset as changed.
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

        $filter = AssetUsageFilter::create()
            ->withAsset($this->persistenceManager->getIdentifierByObject($asset))
            ->includeVariantsOfAsset();

        $workspaceNamesByContentStreamId = [];
        foreach ($this->globalAssetUsageService->findByFilter($filter) as $contentRepositoryId => $usages) {
            $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($contentRepositoryId));
            foreach ($usages as $usage) {
                // TODO: Remove when WorkspaceName is part of the AssetUsageProjection
                $workspaceName = $workspaceNamesByContentStreamId[$contentRepositoryId][$usage->contentStreamId->value] ?? null;
                if ($workspaceName === null) {
                    $workspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId($usage->contentStreamId);
                    if ($workspace === null) {
                        continue;
                    }
                    $workspaceName = $workspace->workspaceName;
                    $workspaceNamesByContentStreamId[$contentRepositoryId][$usage->contentStreamId->value] = $workspaceName;
                }
                //

                $nodeAggregate = $contentRepository->getContentGraph($workspaceName)->findNodeAggregateById($usage->nodeAggregateId);
                if ($nodeAggregate === null) {
                    continue;
                }
                $flushNodeAggregateRequest = FlushNodeAggregateRequest::create(
                    $contentRepository->id,
                    $workspaceName,
                    $nodeAggregate->nodeAggregateId,
                    $nodeAggregate->nodeTypeName,
                    $this->determineAncestorNodeAggregateIds($contentRepository, $workspaceName, $nodeAggregate->nodeAggregateId),
                );

                $this->contentCacheFlusher->flushNodeAggregate($flushNodeAggregateRequest, CacheFlushingStrategy::ON_SHUTDOWN);
            }
        }
    }

    private function determineAncestorNodeAggregateIds(ContentRepository $contentRepository, WorkspaceName $workspaceName, NodeAggregateId $childNodeAggregateId): NodeAggregateIds
    {
        $contentGraph = $contentRepository->getContentGraph($workspaceName);
        $stack = iterator_to_array($contentGraph->findParentNodeAggregates($childNodeAggregateId));

        $ancestorNodeAggregateIds = [];
        while ($stack !== []) {
            $nodeAggregate = array_shift($stack);

            // Prevent infinite loops
            if (!in_array($nodeAggregate->nodeAggregateId, $ancestorNodeAggregateIds, false)) {
                $ancestorNodeAggregateIds[] = $nodeAggregate->nodeAggregateId;
                array_push($stack, ...iterator_to_array($contentGraph->findParentNodeAggregates($nodeAggregate->nodeAggregateId)));
            }
        }

        return NodeAggregateIds::fromArray($ancestorNodeAggregateIds);
    }
}
