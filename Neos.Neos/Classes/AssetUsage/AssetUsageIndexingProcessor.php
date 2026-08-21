<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\Neos\AssetUsage\Service\AssetUsageIndexingService;

final readonly class AssetUsageIndexingProcessor
{
    public function __construct(
        private AssetUsageIndexingService $assetUsageIndexingService
    ) {
    }

    /**
     * @param callable(string $message):void|null $callback
     * @param bool $force Force the indexing processor to index also outdated workspaces with unpublished changes.
     */
    public function buildIndex(ContentRepository $contentRepository, NodeTypeName $nodeTypeName, bool $force = false, ?callable $callback = null): bool
    {
        $variationGraph = $contentRepository->getVariationGraph();

        $allWorkspaces = $contentRepository->findWorkspaces();
        $liveWorkspace = $allWorkspaces->get(WorkspaceName::forLive());
        if ($liveWorkspace === null) {
            throw WorkspaceDoesNotExist::butWasSupposedTo(WorkspaceName::forLive());
        }

        $workspacesDependingOnLive = $allWorkspaces->getDependantWorkspacesRecursively(WorkspaceName::forLive());

        $this->dispatchMessage($callback, sprintf('ContentRepository "%s"', $contentRepository->id->value));

        // Check workspaces first for there state and unpublished changes
        if ($force === false) {
            /** @var Workspace $workspace */
            $dirtyWorkspacesWithUnpublishedChanges = [];
            foreach ($workspacesDependingOnLive as $workspace) {
                if ($workspace->status === WorkspaceStatus::OUTDATED && $workspace->hasPublishableChanges() === true) {
                    $dirtyWorkspacesWithUnpublishedChanges[] = $workspace;
                }
            }

            if (count($dirtyWorkspacesWithUnpublishedChanges) > 0) {
                $this->dispatchMessage($callback, "\n!!! Some workspaces are not up to date and have additional unpublished changes. The indexing may produce false asset usages for these cases.");
                $this->dispatchMessage($callback, "\n!!! Please rebase the corresponding workspaces or publish all pending changes. If you still want to run the index, run it with the 'force' option.");
                $this->dispatchMessage($callback, sprintf("\nAffected Workspaces: \n%s\n", implode("\n", array_map(fn ($workspace) => "  - " . $workspace->workspaceName->value, $dirtyWorkspacesWithUnpublishedChanges))));
                return false;
            }
        }

        $this->assetUsageIndexingService->pruneIndex($contentRepository->id);

        /** @var Workspace $workspace */
        foreach ([$liveWorkspace, ...$workspacesDependingOnLive] as $workspace) {

            $this->dispatchMessage($callback, sprintf('  Workspace: %s', $workspace->workspaceName->value));

            // We do not need to index workspaces without any changes, as they are already indexed by baseworkspace
            if (!$workspace->workspaceName->isLive() && !$workspace->hasPublishableChanges()) {
                $this->dispatchMessage($callback, '    ... skipped, no changes to baseworkspace');
                continue;
            }

            $contentGraph = $contentRepository->getContentGraph($workspace->workspaceName);

            $dimensionSpacePoints = $variationGraph->getDimensionSpacePoints();

            $rootNodeAggregate = $contentGraph->findRootNodeAggregateByType(
                $nodeTypeName
            );
            if ($rootNodeAggregate === null) {
                $this->dispatchMessage($callback, sprintf('    ERROR: %s', "Root node aggregate was not found."));
                continue;
            }
            $rootNodeAggregateId = $rootNodeAggregate->nodeAggregateId;

            foreach ($dimensionSpacePoints as $dimensionSpacePoint) {
                $this->dispatchMessage($callback, sprintf('    DimensionSpacePoint: %s', $dimensionSpacePoint->toJson()));

                $subgraph = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::createEmpty());
                $childNodes = iterator_to_array($subgraph->findChildNodes($rootNodeAggregateId, FindChildNodesFilter::create()));

                while ($childNodes !== []) {
                    /** @var Node $childNode */
                    $childNode = array_shift($childNodes);
                    if (!$childNode->originDimensionSpacePoint->equals($childNode->dimensionSpacePoint)) {
                        continue;
                    }

                    $nodeType = $contentRepository->getNodeTypeManager()->getNodeType($childNode->nodeTypeName);
                    if ($nodeType === null) {
                        return false;
                    }
                    $this->assetUsageIndexingService->updateIndex($contentRepository->id, $childNode, $nodeType, $allWorkspaces);
                    array_push($childNodes, ...iterator_to_array($subgraph->findChildNodes($childNode->aggregateId, FindChildNodesFilter::create())));
                }
            }
        }
        return true;
    }

    private function dispatchMessage(?callable $callback, string $value): void
    {
        if ($callback === null) {
            return;
        }

        $callback($value);
    }
}
