<?php

namespace Neos\Media\Browser\Controller;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Service\UserService;
use Neos\Neos\Domain\Service\UserService as DomainUserService;
use Neos\Neos\AssetUsage\Dto\AssetUsageReference;
use Neos\Neos\Domain\Service\SiteNodeUtility;
use Neos\Neos\Domain\Model\Site;

/**
 * Controller for asset usage handling
 *
 * @Flow\Scope("singleton")
 */
class UsageController extends ActionController
{
    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var DomainUserService
     */
    protected $domainUserService;

    /**
     * @Flow\Inject
     * @var SiteNodeUtility
     */
    protected $siteNodeUtility;

    /**
     * Get Related Nodes for an asset
     *
     * @param AssetInterface $asset
     * @return void
     */
    public function relatedNodesAction(AssetInterface $asset)
    {
        $currentContentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $currentContentRepository = $this->contentRepositoryRegistry->get($currentContentRepositoryId);
        $userWorkspaceName = WorkspaceName::fromString($this->userService->getPersonalWorkspaceName());
        $userWorkspace = $currentContentRepository->getWorkspaceFinder()->findOneByName($userWorkspaceName);

        $usageReferences = $this->assetService->getUsageReferences($asset);
        $relatedNodes = [];
        $inaccessibleRelations = [];

        $existingSites = $this->siteRepository->findAll();

        foreach ($usageReferences as $usage) {
            $inaccessibleRelation = [
                'type' => get_class($usage),
                'usage' => $usage
            ];

            if (!$usage instanceof AssetUsageReference) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $contentRepository = $this->contentRepositoryRegistry->get($usage->getContentRepositoryId());

            $nodeAggregate = $contentRepository->getContentGraph()->findNodeAggregateById(
                $usage->getContentStreamId(),
                $usage->getNodeAggregateId()
            );
            try {
                $nodeType = $contentRepository->getNodeTypeManager()->getNodeType($nodeAggregate->nodeTypeName);
            } catch (NodeTypeNotFoundException $e) {
                $nodeType = null;
            }
            $workspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId($usage->getContentStreamId());
            $accessible = $this->domainUserService->currentUserCanReadWorkspace($workspace);

            $inaccessibleRelation['nodeIdentifier'] = $usage->getNodeAggregateId();
            $inaccessibleRelation['workspaceName'] = $workspace->workspaceName->name;
            $inaccessibleRelation['workspace'] = $workspace;
            $inaccessibleRelation['nodeType'] = $nodeType;
            $inaccessibleRelation['accessible'] = $accessible;

            if (!$accessible) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $node = $contentRepository->getContentGraph()->findNodeByIdAndOriginDimensionSpacePoint(
                $usage->getContentStreamId(),
                $usage->getNodeAggregateId(),
                $usage->getOriginDimensionSpacePoint()
            );

            // this should actually never happen.
            if (!$node) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $flowQuery = new FlowQuery([$node]);
            $documentNode = $flowQuery->closest('[instanceof Neos.Neos:Document]')->get(0);
            // this should actually never happen, too.
            if (!$documentNode) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }


            $siteNode = $this->siteNodeUtility->findSiteNode($node);
            foreach ($existingSites as $existingSite) {
                /** @var Site $existingSite * */
                if ($siteNode->nodeName->equals($existingSite->getNodeName()->toNodeName())) {
                    $site = $existingSite;
                }
            }

            $relatedNodes[$site->getNodeName()->value]['site'] = $site;
            $relatedNodes[$site->getNodeName()->value]['nodes'][] = [
                'node' => $node,
                'workspace' => $workspace,
                'documentNode' => $documentNode
            ];
        }

        $this->view->assignMultiple([
            'totalUsageCount' => count($usageReferences),
            'nodeUsageClass' => AssetUsageReference::class,
            'asset' => $asset,
            'inaccessibleRelations' => $inaccessibleRelations,
            'relatedNodes' => $relatedNodes,
            'contentDimensions' => $currentContentRepository->getContentDimensionSource()->getContentDimensionsOrderedByPriority(),
            'userWorkspace' => $userWorkspace
        ]);
    }


}
