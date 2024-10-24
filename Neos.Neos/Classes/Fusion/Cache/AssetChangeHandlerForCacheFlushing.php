<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\GlobalAssetUsageService;

class AssetChangeHandlerForCacheFlushing
{
    /** @var array<string, array<string, WorkspaceName[]>> */
    private array $workspaceRuntimeCache = [];

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
            ->groupByWorkspaceName()
            ->groupByNodeAggregate()
            ->includeVariantsOfAsset();

        foreach ($this->globalAssetUsageService->findByFilter($filter) as $contentRepositoryId => $usages) {
            $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($contentRepositoryId));

            foreach ($usages as $usage) {
                $workspaceNames = $this->getWorkspaceNameAndChildWorkspaceNames($contentRepository, $usage->workspaceName);

                foreach ($workspaceNames as $workspaceName) {
                    $contentGraph = $contentRepository->getContentGraph($workspaceName);
                    $nodeAggregate = $contentGraph->findNodeAggregateById($usage->nodeAggregateId);
                    if ($nodeAggregate === null) {
                        continue;
                    }
                    $flushNodeAggregateRequest = FlushNodeAggregateRequest::create(
                        $contentRepository->id,
                        $workspaceName,
                        $nodeAggregate->nodeAggregateId,
                        $nodeAggregate->nodeTypeName,
                        $contentGraph->findAncestorNodeAggregateIds($nodeAggregate->nodeAggregateId),
                    );

                    $this->contentCacheFlusher->flushNodeAggregate($flushNodeAggregateRequest, CacheFlushingStrategy::ON_SHUTDOWN);
                }
            }
        }
    }

    /**
     * @return WorkspaceName[]
     */
    private function getWorkspaceNameAndChildWorkspaceNames(ContentRepository $contentRepository, WorkspaceName $workspaceName): array
    {
        if (!isset($this->workspaceRuntimeCache[$contentRepository->id->value][$workspaceName->value])) {
            $workspaceNames = [];
            $workspace = $contentRepository->findWorkspaceByName($workspaceName);
            if ($workspace !== null) {
                $stack[] = $workspace;

                while ($stack !== []) {
                    $workspace = array_shift($stack);
                    $workspaceNames[] = $workspace->workspaceName;

                    $stack = array_merge($stack, iterator_to_array($contentRepository->findWorkspaces()->getDependantWorkspaces($workspace->workspaceName)));
                }
            }
            $this->workspaceRuntimeCache[$contentRepository->id->value][$workspaceName->value] = $workspaceNames;
        }

        return $this->workspaceRuntimeCache[$contentRepository->id->value][$workspaceName->value];
    }
}
