<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Content\NodeAggregate;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\Fusion\Core\Cache\ContentCache;
use Psr\Log\LoggerInterface;

/**
 * This service flushes Fusion content caches triggered by node changes.
 *
 * It is called in two scenarios:
 *
 * - when the projection changes: In this case, it is triggered by {@see CacheAwareGraphProjectorFactory} which creates
 *   {@see CacheFlushJob} instances (which in turn flush the cache).
 *   This is the relevant case if publishing a workspace
 *   - where we f.e. need to flush the cache for Live asynchronously.
 *
 * - explicitly through a Backend API request when we change the projection, block, and then render new content. In this
 *   scenario, it is important to flush the caches BETWEEN updating the projection and rendering the new content - this
 *   only works through an explicit call to {@see ContentCacheFlusher::flushNodeAggregate()}
 *   or {@see ContentCacheFlusher::scheduleFlushNodeAggregate()}.
 *
 * The method registerNodeChange() is triggered manually in the respective UI packages.
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
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * Main entry point to *directly* flush the caches of a given NodeAggregate
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return void
     */
    public function flushNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): void {
        $doFlushContentCache = $this->scheduleFlushNodeAggregate($contentStreamIdentifier, $nodeAggregateIdentifier);
        $doFlushContentCache();
    }

    /**
     * Sometimes, you need to defer figuring out what to flush and the actual flushing to a later point in time.
     * For example, when removing a node, we need to figure out what to flush while the node still exists,
     * but do the flushing later when the node was removed.
     *
     * This can be done with this method: When calling this method, it *directly* finds out what needs to be flushed.
     * The flushing itself, however, must then be triggered by calling the returned function.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return callable execute this function to actually trigger the content cache flushing
     */
    public function scheduleFlushNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): callable {
        $tagsToFlush = [];

        $tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        $this->registerChangeOnNodeIdentifier($contentStreamIdentifier, $nodeAggregateIdentifier, $tagsToFlush);
        $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier
        );
        if (!$nodeAggregate) {
            // Node Aggregate was removed in the meantime, so no need to clear caches on this one anymore.
            return function () {
            };
        }

        $this->registerChangeOnNodeType(
            $nodeAggregate->getNodeTypeName(),
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $tagsToFlush
        );

        $parentNodeAggregates = [];
        foreach (
            $this->contentGraph->findParentNodeAggregates(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier
            ) as $parentNodeAggregate
        ) {
            $parentNodeAggregates[] = $parentNodeAggregate;
        }
        // we do not need these variables anymore here
        unset($contentStreamIdentifier, $nodeAggregateIdentifier);

        while ($nodeAggregate = array_shift($parentNodeAggregates)) {
            assert($nodeAggregate instanceof NodeAggregate);
            $tagName = 'DescendantOf_%' . $nodeAggregate->getContentStreamIdentifier()
                . '%_' . $nodeAggregate->getIdentifier();
            $tagsToFlush[$tagName] = sprintf(
                'which were tagged with "%s" because node "%s" has changed.',
                $tagName,
                $nodeAggregate->getIdentifier()
            );

            // Legacy
            $legacyTagName = 'DescendantOf_' . $nodeAggregate->getIdentifier();
            $tagsToFlush[$legacyTagName] = sprintf(
                'which were tagged with legacy "%s" because node "%s" has changed.',
                $legacyTagName,
                $nodeAggregate->getIdentifier()
            );

            foreach (
                $this->contentGraph->findParentNodeAggregates(
                    $nodeAggregate->getContentStreamIdentifier(),
                    $nodeAggregate->getIdentifier()
                ) as $parentNodeAggregate
            ) {
                $parentNodeAggregates[] = $parentNodeAggregate;
            }
        }
        return function () use ($tagsToFlush) {
            $this->flushTags($tagsToFlush);
        };
    }


    /**
     * Please use registerNodeChange() if possible. This method is a low-level api. If you do use this method make sure
     * that $cacheIdentifier contains the workspacehash as well as the node identifier:
     * $workspaceHash .'_'. $nodeIdentifier
     * The workspacehash can be received via $this->getCachingHelper()->renderWorkspaceTagForContextNode($workpsacename)
     *
     * @param array<string,string> &$tagsToFlush
     */
    private function registerChangeOnNodeIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        array &$tagsToFlush
    ): void {
        $cacheIdentifier = '%' . $contentStreamIdentifier . '%_' . $nodeAggregateIdentifier;
        $tagsToFlush['Node_' . $cacheIdentifier] = sprintf(
            'which were tagged with "Node_%s" because that identifier has changed.',
            $cacheIdentifier
        );
        $tagName = 'DescendantOf_' . $cacheIdentifier;
        $tagsToFlush[$tagName] = sprintf(
            'which were tagged with "%s" because node "%s" has changed.',
            $tagName,
            $cacheIdentifier
        );

        // Legacy
        $cacheIdentifier = (string)$nodeAggregateIdentifier;
        $tagsToFlush['Node_' . $cacheIdentifier] = sprintf(
            'which were tagged with "Node_%s" because that identifier has changed.',
            $cacheIdentifier
        );
        $tagName = 'DescendantOf_' . $cacheIdentifier;
        $tagsToFlush[$tagName] = sprintf(
            'which were tagged with "%s" because node "%s" has changed.',
            $tagName,
            $cacheIdentifier
        );
    }

    /**
     * This is a low-level api. Please use registerNodeChange() if possible. Otherwise make sure that $nodeTypePrefix
     * is set up correctly and contains the workspacehash wich can be received via
     * $this->getCachingHelper()->renderWorkspaceTagForContextNode($workpsacename)
     *
     * @param array<string,string> &$tagsToFlush
     */
    private function registerChangeOnNodeType(
        NodeTypeName $nodeTypeName,
        ContentStreamIdentifier $contentStreamIdentifier,
        ?NodeAggregateIdentifier $referenceNodeIdentifier,
        array &$tagsToFlush
    ): void {
        try {
            $nodeTypesToFlush = $this->getAllImplementedNodeTypeNames(
                $this->nodeTypeManager->getNodeType((string)$nodeTypeName)
            );
        } catch (NodeTypeNotFoundException $e) {
            // as a fallback, we flush the single NodeType
            $nodeTypesToFlush = [(string)$nodeTypeName];
        }

        foreach ($nodeTypesToFlush as $nodeTypeNameToFlush) {
            $tagsToFlush['NodeType_%' . $contentStreamIdentifier . '%_' . $nodeTypeNameToFlush] = sprintf(
                'which were tagged with "NodeType_%s" because node "%s" has changed and was of type "%s".',
                $nodeTypeNameToFlush,
                ($referenceNodeIdentifier ?: ''),
                $nodeTypeName
            );

            // Legacy, but still used
            $tagsToFlush['NodeType_' . $nodeTypeNameToFlush] = sprintf(
                'which were tagged with "NodeType_%s" because node "%s" has changed and was of type "%s".',
                $nodeTypeNameToFlush,
                ($referenceNodeIdentifier ?: ''),
                $nodeTypeName
            );
        }
    }


    /**
     * Flush caches according to the previously registered node changes.
     *
     * @param array<string,string> $tagsToFlush
     */
    protected function flushTags(array $tagsToFlush): void
    {
        foreach ($tagsToFlush as $tag => $logMessage) {
            $affectedEntries = $this->contentCache->flushByTag($tag);
            if ($affectedEntries > 0) {
                $this->systemLogger->debug(sprintf(
                    'Content cache: Removed %s entries %s',
                    $affectedEntries,
                    $logMessage
                ));
            }
        }
    }

    /**
     * @param NodeType $nodeType
     * @return array<string>
     */
    protected function getAllImplementedNodeTypeNames(NodeType $nodeType)
    {
        $self = $this;
        $types = array_reduce(
            $nodeType->getDeclaredSuperTypes(),
            function (array $types, NodeType $superType) use ($self) {
                return array_merge($types, $self->getAllImplementedNodeTypeNames($superType));
            },
            [$nodeType->getName()]
        );

        $types = array_unique($types);
        return $types;
    }
}
