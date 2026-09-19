<?php

/*
 * This file is part of the Neos.Restore.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Controller;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm\SearchTerm;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Security\Context;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;
use Neos\Workspace\Ui\Domain\TrashBin;
use Neos\Workspace\Ui\Domain\TrashBin\TrashBinPagination;
use Neos\Workspace\Ui\Domain\TrashBin\TrashBinSorting;
use Neos\Workspace\Ui\ViewModel\Restore\RestorableNode;
use Neos\Workspace\Ui\ViewModel\Restore\RestorableNodes;
use Neos\Workspace\Ui\ViewModel\Restore\RestoreListItem;
use Neos\Workspace\Ui\ViewModel\Restore\RestoreListItems;
use Neos\Workspace\Ui\ViewModel\Restore\RestoreListItemVariantDetails;
use Neos\Workspace\Ui\ViewModel\Restore\RestoreListItemVariantDetailsCollection;

/**
 * The Neos Restore module controller
 *
 * @internal for communication within the Workspace UI only
 */
#[Flow\Scope('singleton')]
class RestoreController extends AbstractModuleController
{
    protected $defaultViewObjectName = FusionView::class;

    #[Flow\Inject]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected Context $securityContext;

    #[Flow\Inject]
    protected UserService $userService;

    #[Flow\Inject]
    protected Translator $translator;

    #[Flow\Inject]
    protected TrashBin $trashBin;

    #[Flow\Inject]
    protected ContentRepositoryAuthorizationService $authorizationService;

    #[Flow\Inject]
    protected WorkspaceService $workspaceService;

    public function indexAction(): void
    {
        $currentUser = $this->userService->getCurrentUser();
        if (!$currentUser) {
            throw new \Exception('No user is logged in', 1761047616);
        }
        $siteDetectionResult = SiteDetectionResult::fromRequest($this->request->getHttpRequest());
        $workspace = $this->workspaceService->getPersonalWorkspaceForUser($siteDetectionResult->contentRepositoryId, $currentUser->getId());

        $this->redirect(actionName: 'show', arguments: ['workspaceName' => $workspace->workspaceName->value]);
    }

    /**
     * Display a list of unpublished content
     */
    public function showAction(WorkspaceName $workspaceName, ?string $sorting = null, int $page = 1, string $searchTerm = ''): void
    {
        $searchTermObject = $searchTerm ? SearchTerm::fulltext($searchTerm) : null;
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $sortingObject = $sorting ? TrashBinSorting::fromJsonString($sorting) : TrashBinSorting::default();

        $numberOfItems =  $this->trashBin->countItemsByWorkspaceName($contentRepositoryId, $workspaceName, $searchTermObject);
        $offset = ($page - 1) * TrashBinPagination::DEFAULT_LIMIT;
        $pagination = TrashBinPagination::create($offset, TrashBinPagination::DEFAULT_LIMIT);
        $numberOfPages = (int)ceil($numberOfItems / TrashBinPagination::DEFAULT_LIMIT);
        $displayPagination = $this->paginationRange($numberOfPages, $page);

        $contentGraph = $contentRepository->getContentGraph($workspaceName);
        $workspace = $contentRepository->findWorkspaceByName($workspaceName);

        $listItems = [];
        foreach (
            $this->trashBin->findItemsByWorkspaceNameWithParameters(
                contentRepositoryId: $contentRepositoryId,
                workspaceName: $workspaceName,
                sorting: $sortingObject,
                pagination: $pagination,
                searchTerm: $searchTermObject,
            ) as $trashBinItem
        ) {
            $nodeAggregate = $contentGraph->findNodeAggregateById($trashBinItem->nodeAggregateId);
            if (!$nodeAggregate) {
                continue;
            }
            $details = [];
            foreach (
                $nodeAggregate->occupiedDimensionSpacePoints->getIntersection(
                    OriginDimensionSpacePointSet::fromDimensionSpacePointSet($trashBinItem->affectedDimensionSpacePoints)
                ) as $originDimensionSpacePoint
            ) {
                $subgraph = $contentGraph->getSubgraph(
                    $originDimensionSpacePoint->toDimensionSpacePoint(),
                    VisibilityConstraints::createEmpty()
                );
                $removedNode = $nodeAggregate->getNodeByOccupiedDimensionSpacePoint($originDimensionSpacePoint);

                $dimensionValueLabels = [];
                foreach ($originDimensionSpacePoint->coordinates as $id => $coordinate) {
                    $contentDimension = new ContentDimensionId($id);
                    $dimensionValueLabels[] = $contentRepository->getContentDimensionSource()
                        ->getDimension($contentDimension)
                        ?->getValue($coordinate)
                        ?->configuration['label'] ?? $coordinate;
                }

                $details[] = new RestoreListItemVariantDetails(
                    label: $this->nodeLabelGenerator->getLabel($removedNode),
                    ancestorLabels: array_map(
                        fn (Node $ancestor): string => $this->nodeLabelGenerator->getLabel($ancestor),
                        iterator_to_array($subgraph->findAncestorNodes(
                            $trashBinItem->nodeAggregateId,
                            FindAncestorNodesFilter::create()
                        )->reverse())
                    ),
                    dimensionValueLabels: $dimensionValueLabels
                );
            }
            $nodeType = $contentRepository->getNodeTypeManager()->getNodeType($nodeAggregate->nodeTypeName);

            // we assume the cr user id is a neos-user id even though there could be other values. The cr's "system" user is excluded as it does not make sense to the neos user/account world.
            $user = $trashBinItem->userId && !$trashBinItem->userId->isSystemUser() ? $this->userService->findUserById(UserId::fromString($trashBinItem->userId->value)) : null;

            $nodeTypeLabel = $nodeType?->getLabel() ?: '';
            if (\substr_count($nodeTypeLabel, ':') === 2) {
                [$packageKey, $sourceName, $labelId] = explode(':', $nodeTypeLabel);
                $sourceName = \str_replace('.', '/', $sourceName);

                $nodeTypeLabel = $this->translator->translateById(
                    labelId: $labelId,
                    sourceName: $sourceName,
                    packageKey: $packageKey,
                ) ?: $nodeTypeLabel;
            }
            $listItems[] = new RestoreListItem(
                nodeAggregateId: $trashBinItem->nodeAggregateId,
                icon: $nodeType?->getFullConfiguration()['ui']['icon'],
                nodeTypeLabel: $nodeTypeLabel,
                details: RestoreListItemVariantDetailsCollection::fromArray($details),
                deletionUserName: $user
                    ? $user->getName()->getFullName()
                    : (
                        $trashBinItem->userId
                            ? sprintf('[non-existing user %s]', $trashBinItem->userId)
                            : '[no user metadata]'
                    ),
                deleteTime: $trashBinItem->deleteTime,
            );
        }
        $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspaceName);

        $this->view->assignMultiple([
            'workspaceName' => $workspaceName->value,
            'workspaceLabel' => $workspaceMetadata->title->value,
            'restoreListItems' => $listItems ? RestoreListItems::fromArray($listItems) : array(),
            'flashMessages' => $this->controllerContext->getFlashMessageContainer()->getMessagesAndFlush(),
            'sorting' => $sortingObject,
            'searchTerm' => $searchTerm,
            'pagination' => $displayPagination,
            'currentPage' => $page,
            'workspaceIsOutdated' => $workspace?->status !== WorkspaceStatus::UP_TO_DATE,
            'enableRestoreButtons' => $this->authorizationService->getWorkspacePermissions(
                $contentRepositoryId,
                $workspaceName,
                $this->securityContext->getRoles(),
                $this->userService->getCurrentUser()?->getId(),
            )->write
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    protected function paginationRange(int $numberOfPages, int $currentPage): array
    {
        $maximumNumberOfLinks = TrashBinPagination::MAXIMUM_NUMBER_OF_LINKS;
        if ($maximumNumberOfLinks > $numberOfPages) {
            $maximumNumberOfLinks = $numberOfPages;
        }
        $delta = floor($maximumNumberOfLinks / 2);
        $displayRangeStart = $currentPage - $delta;
        $displayRangeEnd = $currentPage + $delta + ($maximumNumberOfLinks % 2 === 0 ? 1 : 0);
        if ($displayRangeStart < 1) {
            $displayRangeEnd -= $displayRangeStart - 1;
        }
        if ($displayRangeEnd > $numberOfPages) {
            $displayRangeStart -= ($displayRangeEnd - $numberOfPages);
        }
        $displayRangeStart = (int)max($displayRangeStart, 1);
        $displayRangeEnd = (int)min($displayRangeEnd, $numberOfPages);

        $pages = [];
        for ($i = $displayRangeStart; $i <= $displayRangeEnd; $i++) {
            $pages[] = ['number' => $i, 'isCurrent' => ($i === $currentPage)];
        }

        $pagination = [
            'pages' => $pages,
            'current' => $currentPage,
            'numberOfPages' => $numberOfPages,
            'displayRangeStart' => $displayRangeStart,
            'displayRangeEnd' => $displayRangeEnd,
            'hasLessPages' => $displayRangeStart > 2,
            'hasMorePages' => $displayRangeEnd + 1 < $numberOfPages
        ];

        if ($currentPage < $numberOfPages) {
            $pagination['nextPage'] = $currentPage + 1;
        }
        if ($currentPage > 1) {
            $pagination['previousPage'] = $currentPage - 1;
        }
        return $pagination;
    }

    public function restoreNodeConfirmationAction(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $this->requireWorkspaceToBeInSync($workspaceName, $contentRepository);
        $dimensionSpacePointsToRestoreIn = $this->requireDimensionSpacePointsTheNodeAggregateIsRemovedIn(
            $nodeAggregateId,
            $workspaceName,
            $contentRepository
        );
        $contentGraph = $contentRepository->getContentGraph($workspaceName);
        /**
         * @var NodeAggregate $nodeAggregate
         * (already enforced by requireDimensionSpacePointTheNodeAggregateIsRemovedIn)
         */
        $nodeAggregate = $contentGraph->findNodeAggregateById($nodeAggregateId);

        $nodeForLabel = null;
        $currentInterfaceLanguage = $this->userService->getCurrentUser()?->getPreferences()->getInterfaceLanguage();
        if ($currentInterfaceLanguage) {
            foreach ($dimensionSpacePointsToRestoreIn as $dimensionSpacePoint) {
                if (
                    $dimensionSpacePoint->getCoordinate(new ContentDimensionId('language'))
                    === $currentInterfaceLanguage
                ) {
                    $origin = $nodeAggregate->getOccupationByCovered($dimensionSpacePoint);
                    $nodeForLabel = $nodeAggregate->getNodeByOccupiedDimensionSpacePoint($origin);
                    break;
                }
            }
        }
        if (!$nodeForLabel) {
            $nodes = iterator_to_array($nodeAggregate->getNodes());
            $nodeForLabel = reset($nodes) ?: null;
        }
        $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspaceName);
        $this->view->assignMultiple([
            'workspaceLabel' => $workspaceMetadata->title->value,
            'nodeAggregateId' => $nodeAggregateId->value,
            'nodeLabel' => $nodeForLabel
                ? $this->nodeLabelGenerator->getLabel($nodeForLabel)
                : $nodeAggregate->nodeTypeName->value,
            'workspaceName' => $workspaceName->value,
            'additionallyRestoredDescendants' => $this->gatherAdditionallyRestoredDescendants(
                nodeAggregateId: $nodeAggregateId,
                descendants: [],
                contentGraph: $contentGraph,
            ),
            'additionallyRestoredAncestors' => $this->gatherAdditionalAncestorsThatWillBeRestored(
                nodeAggregateId: $nodeAggregateId,
                ancestors: RestorableNodes::createEmpty(),
                contentGraph: $contentGraph,
            )->getLabels(),
        ]);
    }

    public function restoreNodeAction(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $this->requireWorkspaceToBeInSync($workspaceName, $contentRepository);

        $this->restoreNodeAggregate(
            nodeAggregateId: $nodeAggregateId,
            workspaceName: $workspaceName,
            contentRepository: $contentRepository,
        );

        foreach (
            $this->gatherAdditionalAncestorsThatWillBeRestored(
                nodeAggregateId: $nodeAggregateId,
                ancestors: RestorableNodes::createEmpty(),
                contentGraph: $contentRepository->getContentGraph($workspaceName),
            ) as $restorableAncestor
        ) {
            $this->restoreNodeAggregate(
                nodeAggregateId: $restorableAncestor->nodeAggregateId,
                workspaceName: $workspaceName,
                contentRepository: $contentRepository,
            );
        }

        $this->addFlashMessage($this->getModuleLabel('restore.feedback.hasBeenRestored'));
        $this->forward(actionName: 'show', arguments: ['workspaceName' => $workspaceName->value]);
    }

    protected function restoreNodeAggregate(
        NodeAggregateId $nodeAggregateId,
        WorkspaceName $workspaceName,
        ContentRepository $contentRepository,
    ): void {
        /** @var non-empty-array<DimensionSpacePoint> $restorableDimensionSpacePoints */
        $restorableDimensionSpacePoints = $this->requireDimensionSpacePointsTheNodeAggregateIsRemovedIn(
            nodeAggregateId: $nodeAggregateId,
            workspaceName: $workspaceName,
            contentRepository: $contentRepository,
        )->points;

        $contentRepository->handle(UntagSubtree::create(
            workspaceName: $workspaceName,
            nodeAggregateId: $nodeAggregateId,
            coveredDimensionSpacePoint: reset($restorableDimensionSpacePoints),
            nodeVariantSelectionStrategy: NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
            tag: NeosSubtreeTag::removed(),
        ));
    }

    protected function requireDimensionSpacePointsTheNodeAggregateIsRemovedIn(
        NodeAggregateId $nodeAggregateId,
        WorkspaceName $workspaceName,
        ContentRepository $contentRepository,
    ): DimensionSpacePointSet {
        $nodeAggregate = $contentRepository->getContentGraph($workspaceName)->findNodeAggregateById($nodeAggregateId);
        $removedCoverage = $nodeAggregate?->getCoveredDimensionsTaggedBy(
            NeosSubtreeTag::removed(),
            true
        ) ?: DimensionSpacePointSet::fromArray([]);
        if ($removedCoverage->isEmpty()) {
            $this->addFlashMessage(
                messageBody: $this->getModuleLabel('restore.feedback.isNotRemoved'),
                severity: Message::SEVERITY_NOTICE,
            );
            $this->throwStatus(409);
        }

        return $removedCoverage;
    }

    protected function requireWorkspaceToBeInSync(WorkspaceName $workspaceName, ContentRepository $contentRepository): void
    {
        $workspace = $contentRepository->findWorkspaceByName($workspaceName);
        if ($workspace?->status !== WorkspaceStatus::UP_TO_DATE) {
            $this->addFlashMessage(
                messageBody: $this->getModuleLabel('restore.feedback.workspaceIsOutOfSync'),
                severity: Message::SEVERITY_WARNING,
            );
            $this->throwStatus(409);
        }
    }

    /**
     * @param array<string,string> $descendants
     * @return array<string,string>
     */
    protected function gatherAdditionallyRestoredDescendants(
        NodeAggregateId $nodeAggregateId,
        array $descendants,
        ContentGraphInterface $contentGraph
    ): array {
        foreach ($contentGraph->findChildNodeAggregates($nodeAggregateId) as $childNodeAggregate) {
            $dimensionSpacePointsTheChildNodeAggregateWillBeRestoredIn
                = $childNodeAggregate->coveredDimensionSpacePoints->getDifference(
                    $childNodeAggregate->getCoveredDimensionsTaggedBy(
                        NeosSubtreeTag::removed(),
                        true
                    )
                );
            if (
                $dimensionSpacePointsTheChildNodeAggregateWillBeRestoredIn->isEmpty()
            ) {
                continue;
            }
            foreach ($dimensionSpacePointsTheChildNodeAggregateWillBeRestoredIn as $dimensionSpacePoint) {
                $origin = $childNodeAggregate->getOccupationByCovered($dimensionSpacePoint);
                $originNode = $childNodeAggregate->getNodeByOccupiedDimensionSpacePoint($origin);
                $descendants[$childNodeAggregate->nodeAggregateId->value . '@' . $origin->toJson()]
                    = $this->nodeLabelGenerator->getLabel($originNode);
            }

            $descendants = $this->gatherAdditionallyRestoredDescendants(
                nodeAggregateId: $childNodeAggregate->nodeAggregateId,
                descendants: $descendants,
                contentGraph: $contentGraph
            );
        }

        return $descendants;
    }

    protected function gatherAdditionalAncestorsThatWillBeRestored(
        NodeAggregateId $nodeAggregateId,
        RestorableNodes $ancestors,
        ContentGraphInterface $contentGraph
    ): RestorableNodes {
        foreach ($contentGraph->findParentNodeAggregates($nodeAggregateId) as $parentNodeAggregate) {
            $dimensionSpacePointsTheParentNodeAggregateWillBeRestoredIn
                = $parentNodeAggregate->getCoveredDimensionsTaggedBy(
                    subtreeTag: NeosSubtreeTag::removed(),
                    withoutInherited: true,
                );

            $additionalAncestors = [];
            foreach ($dimensionSpacePointsTheParentNodeAggregateWillBeRestoredIn as $dimensionSpacePoint) {
                $origin = $parentNodeAggregate->getOccupationByCovered($dimensionSpacePoint);
                $originNode = $parentNodeAggregate->getNodeByOccupiedDimensionSpacePoint($origin);
                $additionalAncestors[$parentNodeAggregate->nodeAggregateId->value . '@' . $origin->toJson()] = new RestorableNode(
                    nodeAggregateId: $parentNodeAggregate->nodeAggregateId,
                    originDimensionSpacePoint: $origin,
                    label: $this->nodeLabelGenerator->getLabel($originNode)
                );
            }
            if ($additionalAncestors !== []) {
                $ancestors = $ancestors->union(RestorableNodes::list(...$additionalAncestors));
            }

            $ancestors = $this->gatherAdditionalAncestorsThatWillBeRestored(
                nodeAggregateId: $parentNodeAggregate->nodeAggregateId,
                ancestors: $ancestors,
                contentGraph: $contentGraph
            );
        }

        return $ancestors;
    }

    /**
     * @param array<mixed> $arguments
     */
    protected function getModuleLabel(string $id, array $arguments = [], mixed $quantity = null): string
    {
        return $this->translator->translateById(
            $id,
            $arguments,
            $quantity,
            null,
            'Main',
            'Neos.Workspace.Ui'
        ) ?: $id;
    }
}
