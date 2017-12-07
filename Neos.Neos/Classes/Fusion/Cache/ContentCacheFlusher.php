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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Domain\Model\Dto\AssetUsageInNodeProperties;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Fusion\Core\Cache\ContentCache;

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
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @var array
     */
    protected $tagsToFlush = array();

    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * Register a node change for a later cache flush. This method is triggered by a signal sent via ContentRepository's Node
     * model or the Neos Publishing Service.
     *
     * @param NodeInterface $node The node which has changed in some way
     * @return void
     */
    public function registerNodeChange(NodeInterface $node)
    {
        $this->tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        $nodeTypesToFlush = $this->getAllImplementedNodeTypes($node->getNodeType());
        foreach ($nodeTypesToFlush as $nodeType) {
            $nodeTypeName = $nodeType->getName();
            $this->tagsToFlush['NodeType_' . $nodeTypeName] = sprintf('which were tagged with "NodeType_%s" because node "%s" has changed and was of type "%s".', $nodeTypeName, $node->getPath(), $node->getNodeType()->getName());
        }

        $this->tagsToFlush['Node_' . $node->getIdentifier()] = sprintf('which were tagged with "Node_%s" because node "%s" has changed.', $node->getIdentifier(), $node->getPath());

        $originalNode = $node;
        while ($node->getDepth() > 1) {
            $node = $node->getParent();
            // Workaround for issue #56566 in Neos.ContentRepository
            if ($node === null) {
                break;
            }
            $tagName = 'DescendantOf_' . $node->getIdentifier();
            $this->tagsToFlush[$tagName] = sprintf('which were tagged with "%s" because node "%s" has changed.', $tagName, $originalNode->getPath());
        }
    }

    /**
     * Deprecated. Please use ContentCacheFlush::registerAssetChange
     *
     * @deprecated
     * @param AssetInterface $asset
     * @return void
     */
    public function registerAssetResourceChange(AssetInterface $asset)
    {
        $this->registerAssetChange($asset);
    }

    /**
     * Fetches possible usages of the asset and registers nodes that use the asset as changed.
     *
     * @param AssetInterface $asset
     * @return void
     */
    public function registerAssetChange(AssetInterface $asset)
    {
        if (!$asset->isInUse()) {
            return;
        }

        foreach ($this->assetService->getUsageReferences($asset) as $reference) {
            if (!$reference instanceof AssetUsageInNodeProperties) {
                continue;
            }

            $this->registerNodeChange($reference->getNode());
        }
    }

    /**
     * Flush caches according to the previously registered node changes.
     *
     * @return void
     */
    public function shutdownObject()
    {
        if ($this->tagsToFlush !== array()) {
            foreach ($this->tagsToFlush as $tag => $logMessage) {
                $affectedEntries = $this->contentCache->flushByTag($tag);
                if ($affectedEntries > 0) {
                    $this->systemLogger->log(sprintf('Content cache: Removed %s entries %s', $affectedEntries, $logMessage), LOG_DEBUG);
                }
            }
        }
    }

    /**
     * @param NodeType $nodeType
     * @return array<NodeType>
     */
    protected function getAllImplementedNodeTypes(NodeType $nodeType)
    {
        $types = array($nodeType);
        foreach ($nodeType->getDeclaredSuperTypes() as $superType) {
            $types = array_merge($types, $this->getAllImplementedNodeTypes($superType));
        }
        return $types;
    }
}
