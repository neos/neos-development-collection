<?php

namespace Neos\EventSourcedNeosAdjustments\FusionCaching;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Psr\Log\LoggerInterface;

/**
 * This service flushes Fusion content caches triggered by node changes.
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
     * @var array
     */
    protected $tagsToFlush = [];

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

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

    private static function fetchParentIfExistsForNode(NodeBasedReadModelInterface $node): ?NodeBasedReadModelInterface
    {
        try {
            return $node->findParentNode();
        } catch (NodeException $e) {
            return null;
        }
    }

    /**
     * Register a node change for a later cache flush. This method is triggered by a signal sent via ContentRepository's Node
     * model or the Neos Publishing Service.
     *
     * @param NodeBasedReadModelInterface $node The node which has changed in some way
     * @return void
     */
    public function registerNodeChange(NodeBasedReadModelInterface $node): void
    {
        $nodeAggregateIdentifier = $node->getNodeAggregateIdentifier();
        $contentStreamIdentifier = $node->getContentStreamIdentifier();

        $this->tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

        $this->registerChangeOnNodeIdentifier($contentStreamIdentifier, $nodeAggregateIdentifier);
        $this->registerChangeOnNodeType($node->getNodeTypeName(), $contentStreamIdentifier, $nodeAggregateIdentifier);


        while ($node = self::fetchParentIfExistsForNode($node)) {
            $tagName = 'DescendantOf_%' . $node->getContentStreamIdentifier() . '%_' . $node->getNodeAggregateIdentifier();
            $this->tagsToFlush[$tagName] = sprintf('which were tagged with "%s" because node "%s" has changed.', $tagName, $node->getNodeAggregateIdentifier());

            // Legacy
            $legacyTagName = 'DescendantOf_' . $node->getNodeAggregateIdentifier();
            $this->tagsToFlush[$legacyTagName] = sprintf('which were tagged with legacy "%s" because node "%s" has changed.', $legacyTagName, $node->getNodeAggregateIdentifier());
        }
    }

    /**
     * Pleas use registerNodeChange() if possible. This method is a low-level api. If you do use this method make sure
     * that $cacheIdentifier contains the workspacehash as well as the node identifier: $workspaceHash .'_'. $nodeIdentifier
     * The workspacehash can be received via $this->getCachingHelper()->renderWorkspaceTagForContextNode($workpsacename)
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     */
    private function registerChangeOnNodeIdentifier(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier)
    {
        $cacheIdentifier = '%' . $contentStreamIdentifier . '%_' . $nodeAggregateIdentifier;
        $this->tagsToFlush['Node_' . $cacheIdentifier] = sprintf('which were tagged with "Node_%s" because that identifier has changed.', $cacheIdentifier);
        $tagName = 'DescendantOf_' . $cacheIdentifier;
        $this->tagsToFlush[$tagName] = sprintf('which were tagged with "%s" because node "%s" has changed.', $tagName, $cacheIdentifier);

        // Legacy
        $cacheIdentifier = (string)$nodeAggregateIdentifier;
        $this->tagsToFlush['Node_' . $cacheIdentifier] = sprintf('which were tagged with "Node_%s" because that identifier has changed.', $cacheIdentifier);
        $tagName = 'DescendantOf_' . $cacheIdentifier;
        $this->tagsToFlush[$tagName] = sprintf('which were tagged with "%s" because node "%s" has changed.', $tagName, $cacheIdentifier);
    }

    /**
     * This is a low-level api. Please use registerNodeChange() if possible. Otherwise make sure that $nodeTypePrefix
     * is set up correctly and contains the workspacehash wich can be received via
     * $this->getCachingHelper()->renderWorkspaceTagForContextNode($workpsacename)
     *
     * @param NodeTypeName $nodeTypeName
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $referenceNodeIdentifier
     */
    private function registerChangeOnNodeType(NodeTypeName $nodeTypeName, ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $referenceNodeIdentifier = null)
    {
        try {
            $nodeTypesToFlush = $this->getAllImplementedNodeTypeNames($this->nodeTypeManager->getNodeType((string)$nodeTypeName));
        } catch (NodeTypeNotFoundException $e) {
            // as a fallback, we flush the single NodeType
            $nodeTypesToFlush = [(string)$nodeTypeName];
        }

        foreach ($nodeTypesToFlush as $nodeTypeNameToFlush) {
            $this->tagsToFlush['NodeType_%' . $contentStreamIdentifier . '%_' . $nodeTypeNameToFlush] = sprintf('which were tagged with "NodeType_%s" because node "%s" has changed and was of type "%s".', $nodeTypeNameToFlush, ($referenceNodeIdentifier ? $referenceNodeIdentifier : ''), $nodeTypeName);

            // Legacy, but still used
            $this->tagsToFlush['NodeType_' . $nodeTypeNameToFlush] = sprintf('which were tagged with "NodeType_%s" because node "%s" has changed and was of type "%s".', $nodeTypeNameToFlush, ($referenceNodeIdentifier ? $referenceNodeIdentifier : ''), $nodeTypeName);
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
}
