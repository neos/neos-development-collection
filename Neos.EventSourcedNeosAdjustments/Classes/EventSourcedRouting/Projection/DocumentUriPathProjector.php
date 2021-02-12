<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace\Event\DimensionSpacePointWasMoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateTypeWasChanged;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasDisabled;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasEnabled;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasMoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeGeneralizationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePeerVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePropertiesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeSpecializationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\ValueObject\DocumentNodeInfo;
use Neos\EventSourcing\EventListener\AfterInvokeInterface;
use Neos\EventSourcing\EventListener\BeforeInvokeInterface;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Neos\Controller\Exception\NodeNotFoundException;

final class DocumentUriPathProjector implements ProjectorInterface, BeforeInvokeInterface, AfterInvokeInterface
{
    public const TABLE_NAME_DOCUMENT_URIS = 'neos_neos_projection_document_uri';
    public const TABLE_NAME_LIVE_CONTENT_STREAMS = 'neos_neos_projection_document_uri_livecontentstreams';
    public const COLUMN_TYPES_DOCUMENT_URIS = [
        'shortcutTarget' => Types::JSON,
    ];

    private NodeTypeManager $nodeTypeManager;

    private DocumentUriPathFinder $documentUriPathFinder;

    private Connection $dbal;

    public function __construct(NodeTypeManager $nodeTypeManager, DocumentUriPathFinder $documentUriPathFinder, Connection $dbal)
    {
        $this->nodeTypeManager = $nodeTypeManager;
        $this->documentUriPathFinder = $documentUriPathFinder;
        $this->dbal = $dbal;
    }

    public function beforeInvoke(EventEnvelope $_): void
    {
        $this->dbal->beginTransaction();
    }

    public function afterInvoke(EventEnvelope $_): void
    {
        try {
            $this->dbal->commit();
        } catch (ConnectionException $e) {
            throw new \RuntimeException(sprintf('Failed to commit transaction in %s: %s', __METHOD__, $e->getMessage()), 1599580555, $e);
        }
    }

    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        try {
            $this->dbal->insert(self::TABLE_NAME_LIVE_CONTENT_STREAMS, [
                'contentStreamIdentifier' => $event->getNewContentStreamIdentifier(),
                'workspaceName' => $event->getWorkspaceName(),
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to insert root content stream identifier of the root workspace "%s": %s', $event->getWorkspaceName(), $e->getMessage()), 1599646608, $e);
        }
    }

    public function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getCoveredDimensionSpacePoints() as $dimensionSpacePoint) {
            $this->insertNode([
                'uriPath' => '',
                'nodeAggregateIdentifierPath' => $event->getNodeAggregateIdentifier(),
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
            ]);
        }
    }

    public function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        if (!$this->isDocumentNodeType($event->getNodeTypeName())) {
            return;
        }

        $propertyValues = $event->getInitialPropertyValues()->getPlainValues();
        $uriPathSegment = $propertyValues['uriPathSegment'] ?? '';

        $shortcutTarget = null;
        if ($this->isShortcutNodeType($event->getNodeTypeName())) {
            $shortcutTarget = [
                'mode' => $propertyValues['targetMode'] ?? 'firstChildNode',
                'target' => $propertyValues['target'] ?? null,
            ];
        }

        foreach ($event->getCoveredDimensionSpacePoints() as $dimensionSpacePoint) {
            $parentNode = $this->tryGetNode(fn () =>$this->documentUriPathFinder->getByIdAndDimensionSpacePointHash($event->getParentNodeAggregateIdentifier(), $dimensionSpacePoint->getHash()));
            if ($parentNode === null) {
                // this should not happen
                continue;
            }
            /** @var DocumentNodeInfo|null $precedingNode */
            $precedingNode = null;

            if ($event->getSucceedingNodeAggregateIdentifier() === null) {
                $precedingNode = $this->tryGetNode(fn () => $this->documentUriPathFinder->getLastChildNode($parentNode->getNodeAggregateIdentifier(), $dimensionSpacePoint->getHash()));
                if ($precedingNode !== null) {
                    // make the new node the new succeeding node of the previously last child (= insert at the end of all children)
                    $this->updateNode($precedingNode, ['succeedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier()]);
                }
            } else {
                $precedingNode = $this->tryGetNode(fn () => $this->documentUriPathFinder->getPrecedingNode($event->getSucceedingNodeAggregateIdentifier(), $parentNode->getNodeAggregateIdentifier(), $dimensionSpacePoint->getHash()));
                if ($precedingNode !== null) {
                    // make the new node the new succeeding node of the previously preceding node of the specified succeeding node (= re-wire <preceding>-<succeeding> to <preceding>-<new node>)
                    $this->updateNode($precedingNode, ['succeedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier()]);
                }
                $this->updateNodeByIdAndDimensionSpacePointHash($event->getSucceedingNodeAggregateIdentifier(), $dimensionSpacePoint->getHash(), ['precedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier()]);
            }

            $nodeAggregateIdentifierPath = $parentNode->getNodeAggregateIdentifierPath() . '/' . $event->getNodeAggregateIdentifier();
            if ($parentNode->isRoot()) {
                $uriPath = '';
                $siteNodeName = $event->getNodeName();
            } else {
                $uriPath = $parentNode->getUriPath() === '' ? $uriPathSegment : $parentNode->getUriPath() . '/' . $uriPathSegment;
                $siteNodeName = $parentNode->getSiteNodeName();
            }
            $this->insertNode([
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                'uriPath' => $uriPath,
                'nodeAggregateIdentifierPath' => $nodeAggregateIdentifierPath,
                'siteNodeName' => $siteNodeName,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'originDimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
                'parentNodeAggregateIdentifier' => $parentNode->getNodeAggregateIdentifier(),
                'precedingNodeAggregateIdentifier' => $precedingNode !== null ? $precedingNode->getNodeAggregateIdentifier() : null,
                'succeedingNodeAggregateIdentifier' => $event->getSucceedingNodeAggregateIdentifier(),
                'shortcutTarget' => $shortcutTarget,
            ]);
        }
    }

    public function whenNodeAggregateTypeWasChanged(NodeAggregateTypeWasChanged $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        if ($this->isShortcutNodeType($event->getNewNodeTypeName())) {
            // The node has been turned into a shortcut node, but since the shortcut mode is not yet set we'll set it to "firstChildNode" in order to prevent an invalid mode
            $this->updateNodeQuery('SET shortcuttarget = \'{"mode":"firstChildNode","target":null}\' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND shortcuttarget IS NULL', [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
            ]);
        } elseif ($this->isDocumentNodeType($event->getNewNodeTypeName())) {
            $this->updateNodeQuery('SET shortcuttarget = NULL WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND shortcuttarget IS NOT NULL', [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
            ]);
        }
    }

    public function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        $this->copyVariants($event->getNodeAggregateIdentifier(), $event->getSourceOrigin(), $event->getPeerOrigin(), $event->getPeerCoverage());
    }

    public function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        $this->copyVariants($event->getNodeAggregateIdentifier(), $event->getSourceOrigin(), $event->getGeneralizationOrigin(), $event->getGeneralizationCoverage());
    }

    public function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        $this->copyVariants($event->getNodeAggregateIdentifier(), $event->getSourceOrigin(), $event->getSpecializationOrigin(), $event->getSpecializationCoverage());
    }

    private function copyVariants(NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePoint $sourceOrigin, DimensionSpacePoint $targetOrigin, DimensionSpacePointSet $coveredSpacePoints): void
    {
        $sourceNode = $this->tryGetNode(fn () => $this->documentUriPathFinder->getByIdAndDimensionSpacePointHash($nodeAggregateIdentifier, $sourceOrigin->getHash()));
        if ($sourceNode === null) {
            // Probably not a document node
            return;
        }
        foreach ($coveredSpacePoints as $coveredSpacePoint) {
            // Especially when importing a site it can happen that variants are created in a "non-deterministic" order, so we need to first make sure a target variant doesn't exist:
            $this->deleteNodeByIdAndDimensionSpacePointHash($nodeAggregateIdentifier, $coveredSpacePoint->getHash());

            $this->insertNode(
                $sourceNode
                ->withDimensionSpacePoint($coveredSpacePoint)
                ->withOriginDimensionSpacePoint($targetOrigin)
                ->toArray()
            );
        }
    }

    public function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getAffectedDimensionSpacePoints() as $dimensionSpacePoint) {
            $node = $this->tryGetNode(fn () => $this->documentUriPathFinder->getByIdAndDimensionSpacePointHash($event->getNodeAggregateIdentifier(), $dimensionSpacePoint->getHash()));
            if ($node === null) {
                // Probably not a document node
                continue;
            }
            # node is already explicitly disabled
            if ($this->isNodeExplicitlyDisabled($node)) {
                return;
            }
            $this->updateNodeQuery('SET disabled = disabled + 1 WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodeAggregateIdentifier = :nodeAggregateIdentifier OR nodeAggregateIdentifierPath LIKE :childNodeAggregateIdentifierPathPrefix)', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                'childNodeAggregateIdentifierPathPrefix' => $node->getNodeAggregateIdentifierPath() . '/%',
            ]);
        }
    }

    public function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getAffectedDimensionSpacePoints() as $dimensionSpacePoint) {
            $node = $this->tryGetNode(fn () => $this->documentUriPathFinder->getByIdAndDimensionSpacePointHash($event->getNodeAggregateIdentifier(), $dimensionSpacePoint->getHash()));
            if ($node === null) {
                // Probably not a document node
                continue;
            }
            # node is not explicitly disabled, so we must not re-enable it
            if (!$this->isNodeExplicitlyDisabled($node)) {
                return;
            }
            $this->updateNodeQuery('SET disabled = disabled - 1 WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodeAggregateIdentifier = :nodeAggregateIdentifier OR nodeAggregateIdentifierPath LIKE :childNodeAggregateIdentifierPathPrefix)', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $node->getNodeAggregateIdentifier(),
                'childNodeAggregateIdentifierPathPrefix' => $node->getNodeAggregateIdentifierPath() . '/%',
            ]);
        }
    }

    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getAffectedCoveredDimensionSpacePoints() as $dimensionSpacePoint) {
            $node = $this->tryGetNode(fn () => $this->documentUriPathFinder->getByIdAndDimensionSpacePointHash($event->getNodeAggregateIdentifier(), $dimensionSpacePoint->getHash()));
            if ($node === null) {
                // Probably not a document node
                continue;
            }

            $this->disconnectNodeFromSiblings($node);

            $this->deleteNodeQuery('WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodeAggregateIdentifier = :nodeAggregateIdentifier OR nodeAggregateIdentifierPath LIKE :childNodeAggregateIdentifierPathPrefix)', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $node->getNodeAggregateIdentifier(),
                'childNodeAggregateIdentifierPathPrefix' => $node->getNodeAggregateIdentifierPath() . '/%',
            ]);
        }
    }

    public function whenNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        $newPropertyValues = $event->getPropertyValues()->getPlainValues();
        if (!isset($newPropertyValues['uriPathSegment']) && !isset($newPropertyValues['targetMode']) && !isset($newPropertyValues['target'])) {
            return;
        }

        // TODO Can there be more affected dimension space points and how to determine them? see https://github.com/neos/contentrepository-development-collection/issues/163

        $node = $this->tryGetNode(fn () => $this->documentUriPathFinder->getByIdAndDimensionSpacePointHash($event->getNodeAggregateIdentifier(), $event->getOriginDimensionSpacePoint()->getHash()));

        if ($node === null) {
            // probably not a document node
            return;
        }
        if ((isset($newPropertyValues['targetMode']) || isset($newPropertyValues['target'])) && $node->isShortcut()) {
            $shortcutTarget = $node->getShortcutTarget();
            $shortcutTarget = [
                'mode' => $newPropertyValues['targetMode'] ?? $shortcutTarget['mode'],
                'target' => $newPropertyValues['target'] ?? $shortcutTarget['target'],
            ];
            $this->updateNodeByIdAndDimensionSpacePointHash($event->getNodeAggregateIdentifier(), $event->getOriginDimensionSpacePoint()->getHash(), ['shortcutTarget' => $shortcutTarget]);
        }

        if (!isset($newPropertyValues['uriPathSegment'])) {
            return;
        }
        $oldUriPath = $node->getUriPath();
        // homepage -> TODO hacky?
        if ($oldUriPath === '') {
            return;
        }
        /** @var string[] $uriPathSegments */
        $uriPathSegments = explode('/', $oldUriPath);
        $uriPathSegments[array_key_last($uriPathSegments)] = $newPropertyValues['uriPathSegment'];
        $newUriPath = implode('/', $uriPathSegments);

        $this->updateNodeQuery('SET uriPath = CONCAT(:newUriPath, SUBSTRING(uriPath, LENGTH(:oldUriPath) + 1)) WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodeAggregateIdentifier = :nodeAggregateIdentifier OR nodeAggregateIdentifierPath LIKE :childNodeAggregateIdentifierPathPrefix)', [
            'newUriPath' => $newUriPath,
            'oldUriPath' => $oldUriPath,
            'dimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
            'nodeAggregateIdentifier' => $node->getNodeAggregateIdentifier(),
            'childNodeAggregateIdentifierPathPrefix' => $node->getNodeAggregateIdentifierPath() . '/%',
        ]);
    }

    public function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getNodeMoveMappings() as $moveMapping) {
            foreach ($this->documentUriPathFinder->getNodeVariantsById($event->getNodeAggregateIdentifier()) as $node) {
                $parentAssignment = $moveMapping->getNewParentAssignments()->getAssignments()[$node->getDimensionSpacePointHash()] ?? null;
                $newParentNodeAggregateIdentifier = $parentAssignment !== null ? $parentAssignment->getNodeAggregateIdentifier() : $node->getParentNodeAggregateIdentifier();

                $succeedingSiblingAssignment = $moveMapping->getNewSucceedingSiblingAssignments()->getAssignments()[$node->getDimensionSpacePointHash()] ?? null;
                $newSucceedingNodeAggregateIdentifier = $succeedingSiblingAssignment !== null ? $succeedingSiblingAssignment->getNodeAggregateIdentifier() : null;

                $this->moveNode($node, $newParentNodeAggregateIdentifier, $newSucceedingNodeAggregateIdentifier);
            }
        }
    }

    private function moveNode(DocumentNodeInfo $node, NodeAggregateIdentifier $newParentNodeAggregateIdentifier, ?NodeAggregateIdentifier $newSucceedingNodeAggregateIdentifier): void
    {
        $this->disconnectNodeFromSiblings($node);

        $this->connectNodeWithSiblings($node, $newParentNodeAggregateIdentifier, $newSucceedingNodeAggregateIdentifier);

        if ($newParentNodeAggregateIdentifier->equals($node->getParentNodeAggregateIdentifier())) {
            return;
        }
        $newParentNode = $this->tryGetNode(fn () => $this->documentUriPathFinder->getByIdAndDimensionSpacePointHash($newParentNodeAggregateIdentifier, $node->getDimensionSpacePointHash()));
        if ($newParentNode === null) {
            // This should never happen really..
            return;
        }

        $disabledDelta = $newParentNode->getDisableLevel() - $node->getDisableLevel();
        if ($this->isNodeExplicitlyDisabled($node)) {
            $disabledDelta++;
        }
        $this->updateNodeQuery(
            'SET
                nodeAggregateIdentifierPath = TRIM(TRAILING "/" FROM CONCAT(:newParentNodeAggregateIdentifierPath, "/", TRIM(LEADING "/" FROM SUBSTRING(nodeAggregateIdentifierPath, :sourceNodeAggregateIdentifierPathOffset)))),
                uriPath = TRIM("/" FROM CONCAT(:newParentUriPath, "/", TRIM(LEADING "/" FROM SUBSTRING(uriPath, :sourceUriPathOffset)))),
                disabled = disabled + ' . $disabledDelta . '
            WHERE
                dimensionSpacePointHash = :dimensionSpacePointHash
                AND (nodeAggregateIdentifier = :nodeAggregateIdentifier OR nodeAggregateIdentifierPath LIKE :childNodeAggregateIdentifierPathPrefix)
            ',
            [
                'nodeAggregateIdentifier' => $node->getNodeAggregateIdentifier(),
                'newParentNodeAggregateIdentifierPath' => $newParentNode->getNodeAggregateIdentifierPath(),
                'sourceNodeAggregateIdentifierPathOffset' => (int)strrpos($node->getNodeAggregateIdentifierPath(), '/') + 1,
                'newParentUriPath' => $newParentNode->getUriPath(),
                'sourceUriPathOffset' => (int)strrpos($node->getUriPath(), '/') + 1,
                'dimensionSpacePointHash' => $node->getDimensionSpacePointHash(),
                'childNodeAggregateIdentifierPathPrefix' => $node->getNodeAggregateIdentifierPath() . '/%',
            ]
        );
    }

    public function reset(): void
    {
        try {
            $this->dbal->exec('TRUNCATE ' . self::TABLE_NAME_DOCUMENT_URIS);
            $this->dbal->exec('TRUNCATE ' . self::TABLE_NAME_LIVE_CONTENT_STREAMS);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to truncate tables: %s', $e->getMessage()), 1599655382, $e);
        }
    }

    private function isNodeExplicitlyDisabled(DocumentNodeInfo $node): bool
    {
        if (!$node->isDisabled()) {
            return false;
        }
        $parentNode = $this->tryGetNode(fn () => $this->documentUriPathFinder->getByIdAndDimensionSpacePointHash($node->getParentNodeAggregateIdentifier(), $node->getDimensionSpacePointHash()));
        $parentDisabledLevel = $parentNode !== null ? $parentNode->getDisableLevel() : 0;
        return $node->getDisableLevel() - $parentDisabledLevel !== 0;
    }

    private function isDocumentNodeType(NodeTypeName $nodeTypeName): bool
    {
        // HACK: We consider the currently configured node type of the given node. This is a deliberate side effect of this projector!
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->getValue());
        return $nodeType->isOfType('Neos.Neos:Document');
    }

    private function isShortcutNodeType(NodeTypeName $nodeTypeName): bool
    {
        // HACK: We consider the currently configured node type of the given node. This is a deliberate side effect of this projector!
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->getValue());
        return $nodeType->isOfType('Neos.Neos:Shortcut');
    }

    private function isLiveContentStream(ContentStreamIdentifier $contentStreamIdentifier): bool
    {
        return $contentStreamIdentifier->equals($this->documentUriPathFinder->getLiveContentStreamIdentifier());
    }

    private function tryGetNode(\Closure $closure): ?DocumentNodeInfo
    {
        try {
            return $closure();
        } /** @noinspection BadExceptionsProcessingInspection @noinspection PhpRedundantCatchClauseInspection */ catch (NodeNotFoundException $_) {
            return null;
        }
    }

    private function insertNode(array $data): void
    {
        try {
            $this->dbal->insert(self::TABLE_NAME_DOCUMENT_URIS, $data, self::COLUMN_TYPES_DOCUMENT_URIS);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to insert node: %s', $e->getMessage()), 1599646694, $e);
        }
    }

    private function updateNode(DocumentNodeInfo $node, array $data): void
    {
        $this->updateNodeByIdAndDimensionSpacePointHash($node->getNodeAggregateIdentifier(), $node->getDimensionSpacePointHash(), $data);
    }

    private function updateNodeByIdAndDimensionSpacePointHash(NodeAggregateIdentifier $nodeAggregateIdentifier, string $dimensionSpacePointHash, array $data): void
    {
        try {
            $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, $data, compact('nodeAggregateIdentifier', 'dimensionSpacePointHash'), self::COLUMN_TYPES_DOCUMENT_URIS);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to update node "%s": %s', $nodeAggregateIdentifier, $e->getMessage()), 1599646777, $e);
        }
    }

    private function updateNodeQuery(string $query, array $parameters): void
    {
        try {
            $this->dbal->executeQuery('UPDATE ' . self::TABLE_NAME_DOCUMENT_URIS . ' ' . $query, $parameters, self::COLUMN_TYPES_DOCUMENT_URIS);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to update node via custom query: %s', $e->getMessage()), 1599659170, $e);
        }
    }

    private function deleteNodeByIdAndDimensionSpacePointHash(NodeAggregateIdentifier $nodeAggregateIdentifier, string $dimensionSpacePointHash): void
    {
        try {
            $this->dbal->delete(self::TABLE_NAME_DOCUMENT_URIS, compact('nodeAggregateIdentifier', 'dimensionSpacePointHash'), self::COLUMN_TYPES_DOCUMENT_URIS);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to delete node "%s": %s', $nodeAggregateIdentifier, $e->getMessage()), 1599655284, $e);
        }
    }

    private function deleteNodeQuery(string $query, array $parameters): void
    {
        try {
            $this->dbal->executeQuery('DELETE ' . self::TABLE_NAME_DOCUMENT_URIS . ' ' . $query, $parameters, self::COLUMN_TYPES_DOCUMENT_URIS);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to delete node via custom query: %s', $e->getMessage()), 1599659226, $e);
        }
    }

    private function disconnectNodeFromSiblings(DocumentNodeInfo $node): void
    {
        if ($node->hasPrecedingNodeAggregateIdentifier()) {
            $this->updateNodeByIdAndDimensionSpacePointHash($node->getPrecedingNodeAggregateIdentifier(), $node->getDimensionSpacePointHash(), ['succeedingNodeAggregateIdentifier' => $node->hasSucceedingNodeAggregateIdentifier() ? $node->getSucceedingNodeAggregateIdentifier() : null]);
        }
        if ($node->hasSucceedingNodeAggregateIdentifier()) {
            $this->updateNodeByIdAndDimensionSpacePointHash($node->getSucceedingNodeAggregateIdentifier(), $node->getDimensionSpacePointHash(), ['precedingNodeAggregateIdentifier' => $node->hasPrecedingNodeAggregateIdentifier() ? $node->getPrecedingNodeAggregateIdentifier() : null]);
        }
    }

    private function connectNodeWithSiblings(DocumentNodeInfo $node, NodeAggregateIdentifier $parentNodeAggregateIdentifier, ?NodeAggregateIdentifier $newSucceedingNodeAggregateIdentifier): void
    {
        $newPrecedingNode = null;
        if ($newSucceedingNodeAggregateIdentifier !== null) {
            $newPrecedingNode = $this->tryGetNode(fn () => $this->documentUriPathFinder->getPrecedingNode($newSucceedingNodeAggregateIdentifier, $parentNodeAggregateIdentifier, $node->getDimensionSpacePointHash()));

            // update new succeeding node
            $this->updateNodeByIdAndDimensionSpacePointHash($newSucceedingNodeAggregateIdentifier, $node->getDimensionSpacePointHash(), ['precedingNodeAggregateIdentifier' => $node->getNodeAggregateIdentifier()]);
        } else {
            $newPrecedingNode = $this->tryGetNode(fn () => $this->documentUriPathFinder->getLastChildNode($parentNodeAggregateIdentifier, $node->getDimensionSpacePointHash()));
        }
        if ($newPrecedingNode !== null) {

            // update new preceding node
            $this->updateNode($newPrecedingNode, ['succeedingNodeAggregateIdentifier' => $node->getNodeAggregateIdentifier()]);
        }

        // update node itself
        $this->updateNode($node, [
            'parentNodeAggregateIdentifier' => $parentNodeAggregateIdentifier,
            'precedingNodeAggregateIdentifier' => $newPrecedingNode !== null ? $newPrecedingNode->getNodeAggregateIdentifier() : null,
            'succeedingNodeAggregateIdentifier' => $newSucceedingNodeAggregateIdentifier,
        ]);
    }


    public function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event)
    {
        $this->updateNodeQuery(
            'SET dimensionspacepointhash = :newDimensionSpacePointHash WHERE dimensionspacepointhash = :originalDimensionSpacePointHash', [
            'originalDimensionSpacePointHash' => $event->getSource()->getHash(),
            'newDimensionSpacePointHash' => $event->getTarget()->getHash(),
            'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
                // TODO: contentStreamIdentifier restriction HOW?
        ]);

        $this->updateNodeQuery(
            'SET origindimensionspacepointhash = :newDimensionSpacePointHash WHERE origindimensionspacepointhash = :originalDimensionSpacePointHash', [
            'originalDimensionSpacePointHash' => $event->getSource()->getHash(),
            'newDimensionSpacePointHash' => $event->getTarget()->getHash(),
            'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
            // TODO: contentStreamIdentifier restriction HOW?
        ]);
    }

}
