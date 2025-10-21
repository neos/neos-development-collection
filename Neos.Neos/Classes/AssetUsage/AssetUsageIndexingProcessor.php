<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\AssetUsage\Service\AssetUsageIndexingService;

final readonly class AssetUsageIndexingProcessor
{
    public function __construct(
        private AssetUsageIndexingService $assetUsageIndexingService
    ) {
    }

    /**
     * @param callable(string $message):void|null $callback
     */
    public function buildIndex(ContentRepository $contentRepository, NodeTypeName $nodeTypeName, ?callable $callback = null): void
    {
        $variationGraph = $contentRepository->getVariationGraph();

        $allWorkspaces = $contentRepository->findWorkspaces();
        $liveWorkspace = $allWorkspaces->get(WorkspaceName::forLive());
        if ($liveWorkspace === null) {
            throw WorkspaceDoesNotExist::butWasSupposedTo(WorkspaceName::forLive());
        }

        $this->assetUsageIndexingService->pruneIndex($contentRepository->id);

        $workspacesDependingOnLive = $allWorkspaces->getDependantWorkspacesRecursively(WorkspaceName::forLive());

        $this->dispatchMessage($callback, sprintf('ContentRepository "%s"', $contentRepository->id->value));
        foreach ([$liveWorkspace, ...$workspacesDependingOnLive] as $workspace) {
            $contentGraph = $contentRepository->getContentGraph($workspace->workspaceName);
            $this->dispatchMessage($callback, sprintf('  Workspace: %s', $contentGraph->getWorkspaceName()->value));

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
                        return;
                    }
                    $this->assetUsageIndexingService->updateIndex($contentRepository->id, $childNode, $nodeType, $allWorkspaces);
                    array_push($childNodes, ...iterator_to_array($subgraph->findChildNodes($childNode->aggregateId, FindChildNodesFilter::create())));
                }
            }
        }
    }

    private function dispatchMessage(?callable $callback, string $value): void
    {
        if ($callback === null) {
            return;
        }

        $callback($value);
    }
}
