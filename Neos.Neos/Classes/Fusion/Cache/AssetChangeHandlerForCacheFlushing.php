<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\GlobalAssetUsageService;

final readonly class AssetChangeHandlerForCacheFlushing
{
    public function __construct(
        private GlobalAssetUsageService $globalAssetUsageService,
        private ContentRepositoryRegistry $contentRepositoryRegistry,
        private PersistenceManagerInterface $persistenceManager,
        private ContentCacheFlusher $contentCacheFlusher,
        private Context $securityContext,
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
            ->groupByWorkspaceName()
            ->groupByNodeAggregate()
            ->includeVariantsOfAsset();

        $allWorkspaces = null;
        $this->securityContext->withoutAuthorizationChecks(function () use ($filter, &$allWorkspaces) {
            foreach ($this->globalAssetUsageService->findByFilter($filter) as $contentRepositoryId => $usages) {
                $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($contentRepositoryId));

                foreach ($usages as $usage) {
                    $allWorkspaces = $allWorkspaces ??= $contentRepository->findWorkspaces();
                    $usageWorkspace = $allWorkspaces->get($usage->workspaceName);
                    if ($usageWorkspace === null) {
                        continue;
                    }
                    $dependantWorkspaces = $allWorkspaces->getDependantWorkspacesRecursively($usage->workspaceName);
                    foreach ([$usageWorkspace, ...$dependantWorkspaces] as $workspace) {
                        $contentGraph = $contentRepository->getContentGraph($workspace->workspaceName);
                        $nodeAggregate = $contentGraph->findNodeAggregateById($usage->nodeAggregateId);
                        if ($nodeAggregate === null) {
                            continue;
                        }
                        $flushNodeAggregateRequest = FlushNodeAggregateRequest::create(
                            $contentRepository->id,
                            $nodeAggregate->workspaceName,
                            $nodeAggregate->nodeAggregateId,
                            $nodeAggregate->nodeTypeName,
                            $contentGraph->findAncestorNodeAggregateIds($nodeAggregate->nodeAggregateId),
                        );

                        $this->contentCacheFlusher->flushNodeAggregate($flushNodeAggregateRequest, CacheFlushingStrategy::ON_SHUTDOWN);
                    }
                }
            }
        });
    }
}
