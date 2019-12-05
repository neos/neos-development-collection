<?php
namespace Neos\Neos\Fusion\Cache;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Domain\Model\Dto\AssetUsageInNodeProperties;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Neos\Fusion\Helper\CachingHelper;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Psr\Log\LoggerInterface;

/**
 * This service flushes Fusion content caches triggered by node changes.
 *
 * The method registerNodeChange() is triggered by a signal which is configured in the Package class of the Neos.Neos
 * package (this package). Information on changed nodes is collected by this method and the respective Fusion content
 * cache entries are flushed in one operation during Flow's shutdown procedure.
 *
 * @Flow\Scope("singleton")
 */
class ContentCacheFlusher
{
    /**
     * @Flow\Inject
     * @var ContentCache
     */
    protected $contentCache;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @var array
     */
    protected $tagsToFlush = [];

    /**
     * @var CachingHelper
     */
    protected $cachingHelper;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var array
     */
    protected $workspacesToFlush = [];

    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var ContentContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var ContentContext[]
     */
    protected $contexts = [];

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * Register a node change for a later cache flush. This method is triggered by a signal sent via ContentRepository's Node
     * model or the Neos Publishing Service.
     *
     * @param NodeInterface $node The node which has changed in some way
     * @param Workspace $targetWorkspace An optional workspace to flush
     * @return void
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function registerNodeChange(NodeInterface $node, Workspace $targetWorkspace = null): void
    {
        $this->tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        if (empty($this->workspacesToFlush[$node->getWorkspace()->getName()])) {
            $this->resolveWorkspaceChain($node->getWorkspace());
        }

        if ($targetWorkspace !== null && empty($this->workspacesToFlush[$targetWorkspace->getName()])) {
            $this->resolveWorkspaceChain($targetWorkspace);
        }

        if (!array_key_exists($node->getWorkspace()->getName(), $this->workspacesToFlush)) {
            return;
        }

        $this->registerAllTagsToFlushForNodeInWorkspace($node, $node->getWorkspace());
        if ($targetWorkspace !== null) {
            $this->registerAllTagsToFlushForNodeInWorkspace($node, $targetWorkspace);
        }
    }

    /**
     * @param NodeInterface $node
     * @param Workspace $workspace
     */
    protected function registerAllTagsToFlushForNodeInWorkspace(NodeInterface $node, Workspace $workspace): void
    {
        $nodeIdentifier = $node->getIdentifier();

        if (!array_key_exists($workspace->getName(), $this->workspacesToFlush) || is_array($this->workspacesToFlush[$workspace->getName()]) === false) {
            return;
        }

        foreach ($this->workspacesToFlush[$workspace->getName()] as $workspaceName => $workspaceHash) {
            $this->registerChangeOnNodeIdentifier($workspaceHash .'_'. $nodeIdentifier);
            $this->registerChangeOnNodeType($node->getNodeType()->getName(), $nodeIdentifier, $workspaceHash);

            // Still register legacy cache configuration tags
            $this->registerChangeOnNodeIdentifier($nodeIdentifier);
            $this->registerChangeOnNodeType($node->getNodeType()->getName(), $nodeIdentifier, '');

            $nodeInWorkspace = $node;
            while ($nodeInWorkspace->getDepth() > 1) {
                $nodeInWorkspace = $nodeInWorkspace->getParent();
                // Workaround for issue #56566 in Neos.ContentRepository
                if ($nodeInWorkspace === null) {
                    break;
                }
                $tagName = 'DescendantOf_' . $workspaceHash . '_' . $nodeInWorkspace->getIdentifier();
                $this->tagsToFlush[$tagName] = sprintf('which were tagged with "%s" because node "%s" has changed.', $tagName, $node->getPath());

                $legacyTagName = 'DescendantOf_' . $nodeInWorkspace->getIdentifier();
                $this->tagsToFlush[$legacyTagName] = sprintf('which were tagged with legacy "%s" because node "%s" has changed.', $legacyTagName, $node->getPath());
            }
        }
    }

    /**
     * @param Workspace $workspace
     * @return void
     */
    protected function resolveWorkspaceChain(Workspace $workspace)
    {
        $cachingHelper = $this->getCachingHelper();

        $this->workspacesToFlush[$workspace->getName()][$workspace->getName()] = $cachingHelper->renderWorkspaceTagForContextNode($workspace->getName());
        $this->resolveTagsForChildWorkspaces($workspace, $workspace->getName());
    }

    /**
     * @param Workspace $workspace
     * @param string $startingPoint
     * @return void
     */
    protected function resolveTagsForChildWorkspaces(Workspace $workspace, string $startingPoint)
    {
        $cachingHelper = $this->getCachingHelper();
        $this->workspacesToFlush[$startingPoint][$workspace->getName()] = $cachingHelper->renderWorkspaceTagForContextNode($workspace->getName());

        $childWorkspaces = $this->workspaceRepository->findByBaseWorkspace($workspace->getName());
        if ($childWorkspaces->valid()) {
            foreach ($childWorkspaces as $childWorkspace) {
                $this->resolveTagsForChildWorkspaces($childWorkspace, $startingPoint);
            }
        }
    }

    /**
     * Pleas use registerNodeChange() if possible. This method is a low-level api. If you do use this method make sure
     * that $cacheIdentifier contains the workspacehash as well as the node identifier: $workspaceHash .'_'. $nodeIdentifier
     * The workspacehash can be received via $this->getCachingHelper()->renderWorkspaceTagForContextNode($workpsacename)
     *
     * @param string $cacheIdentifier
     */
    public function registerChangeOnNodeIdentifier($cacheIdentifier)
    {
        $this->tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';
        $this->tagsToFlush['Node_' . $cacheIdentifier] = sprintf('which were tagged with "Node_%s" because that identifier has changed.', $cacheIdentifier);

        // Note, as we don't have a node here we cannot go up the structure.
        $tagName = 'DescendantOf_' . $cacheIdentifier;
        $this->tagsToFlush[$tagName] = sprintf('which were tagged with "%s" because node "%s" has changed.', $tagName, $cacheIdentifier);
    }

    /**
     * This is a low-level api. Please use registerNodeChange() if possible. Otherwise make sure that $nodeTypePrefix
     * is set up correctly and contains the workspacehash wich can be received via
     * $this->getCachingHelper()->renderWorkspaceTagForContextNode($workpsacename)
     *
     * @param string $nodeTypeName
     * @param string $referenceNodeIdentifier
     * @param string $nodeTypePrefix
     *
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function registerChangeOnNodeType($nodeTypeName, $referenceNodeIdentifier = null, $nodeTypePrefix = '')
    {
        $this->tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        $nodeTypesToFlush = $this->getAllImplementedNodeTypeNames($this->nodeTypeManager->getNodeType($nodeTypeName));

        if (strlen($nodeTypePrefix) > 0) {
            $nodeTypePrefix = rtrim($nodeTypePrefix, '_') . '_';
        }

        foreach ($nodeTypesToFlush as $nodeTypeNameToFlush) {
            $this->tagsToFlush['NodeType_' . $nodeTypePrefix . $nodeTypeNameToFlush] = sprintf('which were tagged with "NodeType_%s" because node "%s" has changed and was of type "%s".', $nodeTypeNameToFlush, ($referenceNodeIdentifier ? $referenceNodeIdentifier : ''), $nodeTypeName);
        }
    }

    /**
     * Fetches possible usages of the asset and registers nodes that use the asset as changed.
     *
     * @param AssetInterface $asset
     * @return void
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function registerAssetChange(AssetInterface $asset)
    {
        if (!$asset->isInUse()) {
            return;
        }

        $cachingHelper = $this->getCachingHelper();

        foreach ($this->assetService->getUsageReferences($asset) as $reference) {
            if (!$reference instanceof AssetUsageInNodeProperties) {
                continue;
            }

            $workspaceHash = $cachingHelper->renderWorkspaceTagForContextNode($reference->getWorkspaceName());
            $this->securityContext->withoutAuthorizationChecks(function () use ($reference, &$node) {
                $node = $this->getContextForReference($reference)->getNodeByIdentifier($reference->getNodeIdentifier());
            });

            if (!$node instanceof NodeInterface) {
                $this->systemLogger->warning(sprintf('Found a node reference from node with identifier %s in workspace %s to asset %s, but the node could not be fetched.', $reference->getNodeIdentifier(), $reference->getWorkspaceName(), $this->persistenceManager->getIdentifierByObject($asset)), LogEnvironment::fromMethodName(__METHOD__));
                continue;
            }

            $this->registerNodeChange($node);

            $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
            // @see RuntimeContentCache.addTag
            $tagName = 'AssetDynamicTag_' . $workspaceHash . '_' . $assetIdentifier;
            $this->tagsToFlush[$tagName] = sprintf('which were tagged with "%s" because asset "%s" has changed.', $tagName, $assetIdentifier);
        }
    }

    /**
     * Flush caches according to the previously registered node changes.
     *
     * @return void
     */
    public function shutdownObject()
    {
        if ($this->tagsToFlush !== []) {
            foreach ($this->tagsToFlush as $tag => $logMessage) {
                $affectedEntries = $this->contentCache->flushByTag($tag);
                if ($affectedEntries > 0) {
                    $this->systemLogger->debug(sprintf('Content cache: Removed %s entries %s', $affectedEntries, $logMessage));
                }
            }
        }
    }

    /**
     * @param AssetUsageInNodeProperties $assetUsage
     * @return ContentContext
     */
    protected function getContextForReference(AssetUsageInNodeProperties $assetUsage): ContentContext
    {
        $hash = md5(sprintf('%s-%s', $assetUsage->getWorkspaceName(), json_encode($assetUsage->getDimensionValues())));
        if (!isset($this->contexts[$hash])) {
            $this->contexts[$hash] = $this->contextFactory->create([
                'workspaceName' => $assetUsage->getWorkspaceName(),
                'dimensions' => $assetUsage->getDimensionValues(),
                'invisibleContentShown' => true,
                'inaccessibleContentShown' => true
            ]);
        }

        return $this->contexts[$hash];
    }

    /**
     * @param NodeType $nodeType
     * @return array<string>
     */
    protected function getAllImplementedNodeTypeNames(NodeType $nodeType)
    {
        $self = $this;
        $types = array_reduce($nodeType->getDeclaredSuperTypes(), function (array $types, NodeType $superType) use ($self) {
            return array_merge($types, $self->getAllImplementedNodeTypeNames($superType));
        }, [$nodeType->getName()]);

        $types = array_unique($types);
        return $types;
    }

    /**
     * @return CachingHelper
     */
    protected function getCachingHelper(): CachingHelper
    {
        if (!$this->cachingHelper instanceof CachingHelper) {
            $this->cachingHelper = new CachingHelper();
        }

        return $this->cachingHelper;
    }
}
