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

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Psr\Log\LoggerInterface;

/**
 * This service flushes Fusion content caches triggered by node changes.
 *
 * It is called when the projection changes: In this case, it is triggered by {@see GraphProjectorCatchUpHookForCacheFlushing} which
 * calls this method..
 *   This is the relevant case if publishing a workspace
 *   - where we f.e. need to flush the cache for Live.
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
     * Main entry point to *directly* flush the caches of a given NodeAggregate
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return void
     */
    public function flushNodeAggregate(
        ContentRepository $contentRepository,
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): callable {
        $tagsToFlush = [];

        $tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        $this->registerChangeOnNodeIdentifier($contentStreamIdentifier, $nodeAggregateIdentifier, $tagsToFlush);
        $nodeAggregate = $contentRepository->getContentGraph()->findNodeAggregateByIdentifier(
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
            $tagsToFlush,
            $contentRepository
        );

        $parentNodeAggregates = [];
        foreach (
            $contentRepository->getContentGraph()->findParentNodeAggregates(
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
                $contentRepository->getContentGraph()->findParentNodeAggregates(
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
        array &$tagsToFlush,
        ContentRepository $contentRepository
    ): void {
        try {
            $nodeTypesToFlush = $this->getAllImplementedNodeTypeNames(
                $contentRepository->getNodeTypeManager()->getNodeType((string)$nodeTypeName)
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


    /**
     * Fetches possible usages of the asset and registers nodes that use the asset as changed.
     *
     * @throws NodeTypeNotFoundException
     */
    public function registerAssetChange(AssetInterface $asset): void
    {
        // In Nodes only assets are referenced, never asset variants directly. When an asset
        // variant is updated, it is passed as $asset, but since it is never "used" by any node
        // no flushing of corresponding entries happens. Thus we instead us the original asset
        // of the variant.
        if ($asset instanceof AssetVariantInterface) {
            $asset = $asset->getOriginalAsset();
        }

        // TODO: re-implement this based on the code below

        /*
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
                $this->systemLogger->warning(sprintf(
                    'Found a node reference from node with identifier %s in workspace %s to asset %s,'
                        . ' but the node could not be fetched.',
                    $reference->getNodeIdentifier(),
                    $reference->getWorkspaceName(),
                    $this->persistenceManager->getIdentifierByObject($asset)
                ), LogEnvironment::fromMethodName(__METHOD__));
                continue;
            }

            $this->registerNodeChange($node);

            $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
            // @see RuntimeContentCache.addTag
            $tagName = 'AssetDynamicTag_' . $workspaceHash . '_' . $assetIdentifier;
            $this->addTagToFlush(
                $tagName,
                sprintf(
                    'which were tagged with "%s" because asset "%s" has changed.',
                    $tagName,
                    $assetIdentifier
                )
            );
        }*/
    }
}
