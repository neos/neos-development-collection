<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Domain;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm\SearchTerm;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm\SearchTermMatcher;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;
use Neos\Workspace\Ui\Domain\TrashBin\TrashBinPagination;
use Neos\Workspace\Ui\Domain\TrashBin\TrashBinSorting;
use Neos\Workspace\Ui\Domain\TrashBin\TrashItemFinder;
use Neos\Workspace\Ui\Domain\TrashBin\TrashItems;

/**
 * @internal for communication within the Workspace UI only
 */
#[Flow\Scope('singleton')]
class TrashBin
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {
    }

    public function findItemsByWorkspaceNameWithParameters(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        TrashBinSorting $sorting,
        TrashBinPagination $pagination,
        ?SearchTerm $searchTerm,
    ): TrashItems {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $filterToNodeAggregateIds = $this->filterToNodeAggregateIds($contentRepository, $workspaceName, $searchTerm);

        return $contentRepository
            ->projectionState(TrashItemFinder::class)
            ->findItemsByWorkspaceNameWithParameters($workspaceName, $sorting, $pagination, $filterToNodeAggregateIds);
    }

    public function countItemsByWorkspaceName(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        ?SearchTerm $searchTerm,
    ): int {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $filterToNodeAggregateIds = $this->filterToNodeAggregateIds($contentRepository, $workspaceName, $searchTerm);

        return $contentRepository
            ->projectionState(TrashItemFinder::class)
            ->countItemsByWorkspaceName($workspaceName, $filterToNodeAggregateIds);
    }

    private function filterToNodeAggregateIds(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        ?SearchTerm $searchTerm,
    ): ?NodeAggregateIds {
        $filterToNodeAggregateIds = null;
        if ($searchTerm) {
            $filterToNodeAggregateIds = [];
            foreach (
                $contentRepository->getContentGraph($workspaceName)
                    ->findNodeAggregatesTaggedBy(NeosSubtreeTag::removed()) as $taggedNodeAggregate
            ) {
                foreach ($taggedNodeAggregate->getCoveredDimensionsTaggedBy(NeosSubtreeTag::removed(), true) as $taggedDimensionSpacePoint) {
                    $taggedOrigin = OriginDimensionSpacePoint::fromDimensionSpacePoint($taggedDimensionSpacePoint);
                    if ($taggedNodeAggregate->occupiesDimensionSpacePoint($taggedOrigin)) {
                        $removedNode = $taggedNodeAggregate->getNodeByOccupiedDimensionSpacePoint($taggedOrigin);
                        if (SearchTermMatcher::matchesNode($removedNode, $searchTerm)) {
                            $filterToNodeAggregateIds[$removedNode->aggregateId->value] = $removedNode->aggregateId;
                        }
                    }
                }
            }
            $filterToNodeAggregateIds = NodeAggregateIds::fromArray(array_values($filterToNodeAggregateIds));
        }
        return $filterToNodeAggregateIds;
    }
}
