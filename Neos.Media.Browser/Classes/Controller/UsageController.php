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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Model\Dto\AssetUsageInNodeProperties;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Neos\Service\UserService;
use Neos\Neos\Domain\Service\UserService as DomainUserService;

/**
 * Controller for asset usage handling
 *
 * @Flow\Scope("singleton")
 */
class UsageController extends ActionController
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

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
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var DomainUserService
     */
    protected $domainUserService;

    /**
     * Get Related Nodes for an asset
     *
     * @param AssetInterface $asset
     * @return void
     */
    public function relatedNodesAction(AssetInterface $asset)
    {
        $userWorkspace = $this->userService->getPersonalWorkspace();

        $usageReferences = $this->assetService->getUsageReferences($asset);
        $relatedNodes = [];
        $inaccessibleRelations = [];

        $existingSites = $this->siteRepository->findAll();

        foreach ($usageReferences as $usage) {
            $inaccessibleRelation = [
                'type' => get_class($usage),
                'usage' => $usage
            ];

            if (!$usage instanceof AssetUsageInNodeProperties) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            try {
                $nodeType = $this->nodeTypeManager->getNodeType($usage->getNodeTypeName());
            } catch (NodeTypeNotFoundException $e) {
                $nodeType = null;
            }
            /** @var Workspace $workspace */
            $workspace = $this->workspaceRepository->findByIdentifier($usage->getWorkspaceName());
            $accessible = $this->domainUserService->currentUserCanReadWorkspace($workspace);

            $inaccessibleRelation['nodeIdentifier'] = $usage->getNodeIdentifier();
            $inaccessibleRelation['workspaceName'] = $usage->getWorkspaceName();
            $inaccessibleRelation['workspace'] = $workspace;
            $inaccessibleRelation['nodeType'] = $nodeType;
            $inaccessibleRelation['accessible'] = $accessible;

            if (!$accessible) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $node = $this->getNodeFrom($usage);
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

            $site = $node->getContext()->getCurrentSite();
            foreach ($existingSites as $existingSite) {
                /** @var Site $existingSite * */
                $siteNodePath = '/sites/' . $existingSite->getNodeName();
                if ($siteNodePath === $node->getPAth() || strpos($node->getPath(), $siteNodePath . '/') === 0) {
                    $site = $existingSite;
                }
            }

            $relatedNodes[$site->getNodeName()]['site'] = $site;
            $relatedNodes[$site->getNodeName()]['nodes'][] = [
                'node' => $node,
                'documentNode' => $documentNode
            ];
        }

        $this->view->assignMultiple([
            'totalUsageCount' => count($usageReferences),
            'nodeUsageClass' => AssetUsageInNodeProperties::class,
            'asset' => $asset,
            'inaccessibleRelations' => $inaccessibleRelations,
            'relatedNodes' => $relatedNodes,
            'contentDimensions' => $this->contentDimensionPresetSource->getAllPresets(),
            'userWorkspace' => $userWorkspace
        ]);
    }

    /**
     * @param AssetUsageInNodeProperties $assetUsage
     * @return NodeInterface
     */
    private function getNodeFrom(AssetUsageInNodeProperties $assetUsage)
    {
        $context = $this->_contextFactory->create(
            [
            'workspaceName' => $assetUsage->getWorkspaceName(),
            'dimensions' => $assetUsage->getDimensionValues(),
            'invisibleContentShown' => true,
            'removedContentShown' => true]
        );
        return $context->getNodeByIdentifier($assetUsage->getNodeIdentifier());
    }
}
