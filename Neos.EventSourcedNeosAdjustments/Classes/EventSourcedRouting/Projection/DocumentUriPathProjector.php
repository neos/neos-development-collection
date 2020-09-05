<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
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
use Neos\EventSourcing\EventListener\AfterInvokeInterface;
use Neos\EventSourcing\EventListener\BeforeInvokeInterface;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class DocumentUriPathProjector implements ProjectorInterface, BeforeInvokeInterface, AfterInvokeInterface
{
    public const TABLE_NAME_DOCUMENT_URIS = 'neos_neos_projection_document_uri';
    public const TABLE_NAME_LIVE_CONTENT_STREAMS = 'neos_neos_projection_document_uri_livecontentstreams';

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @var string
     */
    private $liveContentStreamIdentifierRuntimeCache;

    public function injectEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->dbal = $entityManager->getConnection();
    }

    public function beforeInvoke(EventEnvelope $_): void
    {
        $this->dbal->beginTransaction();
    }

    public function afterInvoke(EventEnvelope $_): void
    {
        $this->dbal->commit();
    }

    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->dbal->insert(self::TABLE_NAME_LIVE_CONTENT_STREAMS, [
            'contentStreamIdentifier' => $event->getNewContentStreamIdentifier(),
            'workspaceName' => $event->getWorkspaceName(),
        ]);
    }

    public function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getCoveredDimensionSpacePoints() as $dimensionSpacePoint) {
            $this->dbal->insert(self::TABLE_NAME_DOCUMENT_URIS, [
                'uriPath' => '',
                'nodePath' => '',
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
            $precedingNodeAggregateIdentifier = null;


            /** @var array $parentNodePathInfo */
            $parentNodePathInfo = $this->dbal->fetchAssoc('SELECT nodeAggregateIdentifier, uriPath, nodePath, siteNodeName FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND nodeAggregateIdentifier = :nodeAggregateIdentifier LIMIT 1', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $event->getParentNodeAggregateIdentifier(),
            ]);

            if ($event->getSucceedingNodeAggregateIdentifier() === null) {
                /** @var array $precedingNodePathInfo */
                $precedingNodePathInfo = $this->dbal->fetchAssoc('SELECT nodeAggregateIdentifier FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND nodePath REGEXP :nodePathPattern AND succeedingNodeAggregateIdentifier IS NULL', [
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                    'nodePathPattern' => $parentNodePathInfo['nodePath'] . '/[^\/]+$',
                ]);
                if ($precedingNodePathInfo !== false) {
                    $precedingNodeAggregateIdentifier = $precedingNodePathInfo['nodeAggregateIdentifier'];
                    $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                        'succeedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                    ], [
                        'nodeAggregateIdentifier' => $precedingNodePathInfo['nodeAggregateIdentifier'],
                        'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                    ]);
                }
            } else {
                /** @var array $precedingNodePathInfo */
                $precedingNodePathInfo = $this->dbal->fetchAssoc('SELECT nodeAggregateIdentifier FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND nodePath REGEXP :nodePathPattern AND succeedingNodeAggregateIdentifier = :succeedingNodeAggregateIdentifier', [
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                    'nodePathPattern' => $parentNodePathInfo['nodePath'] . '/[^\/]+$',
                    'succeedingNodeAggregateIdentifier' => $event->getSucceedingNodeAggregateIdentifier(),
                ]);
                if ($precedingNodePathInfo !== false) {
                    $precedingNodeAggregateIdentifier = $precedingNodePathInfo['nodeAggregateIdentifier'];
                    $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                        'succeedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                    ], [
                        'nodeAggregateIdentifier' => $precedingNodePathInfo['nodeAggregateIdentifier'],
                        'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                    ]);
                }
                $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                    'precedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                ], [
                    'nodeAggregateIdentifier' => $event->getSucceedingNodeAggregateIdentifier(),
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                ]);
            }


            // TODO reset uri path if parent node === root node

            $nodePath = ($parentNodePathInfo !== false ? $parentNodePathInfo['nodePath'] . '/' : '') . $event->getNodeAggregateIdentifier();
            if ($parentNodePathInfo === false || $parentNodePathInfo['nodePath'] === '') {
                $uriPath = '';
                $siteNodeName = (string)$event->getNodeName();
            } else {
                $uriPath = $parentNodePathInfo['uriPath'] === '' ? $uriPathSegment : $parentNodePathInfo['uriPath'] . '/' . $uriPathSegment;
                $siteNodeName = $parentNodePathInfo['siteNodeName'];
            }
            $this->dbal->insert(self::TABLE_NAME_DOCUMENT_URIS, [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                'uriPath' => $uriPath,
                'nodePath' => $nodePath,
                'siteNodeName' => $siteNodeName,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'originDimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
                'parentNodeAggregateIdentifier' => $parentNodePathInfo['nodeAggregateIdentifier'] ?? null,
                'precedingNodeAggregateIdentifier' => $precedingNodeAggregateIdentifier,
                'succeedingNodeAggregateIdentifier' => $event->getSucceedingNodeAggregateIdentifier(),
                'shortcutTarget' => $shortcutTarget !== null ? json_encode($shortcutTarget, JSON_THROW_ON_ERROR) : null,
            ]);
        }
    }

    public function whenNodeAggregateTypeWasChanged(NodeAggregateTypeWasChanged $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        if ($this->isShortcutNodeType($event->getNewNodeTypeName())) {
            $this->dbal->executeUpdate('UPDATE ' . self::TABLE_NAME_DOCUMENT_URIS . ' SET shortcuttarget = \'{"mode":"firstChildNode","target":null}\' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND shortcuttarget IS NULL', [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
            ]);
        } elseif ($this->isDocumentNodeType($event->getNewNodeTypeName())) {
            $this->dbal->executeUpdate('UPDATE ' . self::TABLE_NAME_DOCUMENT_URIS . ' SET shortcuttarget = NULL WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND shortcuttarget IS NOT NULL', [
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
        /** @var array $sourceData */
        $sourceData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :sourceDimensionSpacePointHash', [
            'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
            'sourceDimensionSpacePointHash' => $sourceOrigin->getHash(),
        ]);
        if ($sourceData === false) {
            // Probably not a document node
            return;
        }
        foreach ($coveredSpacePoints as $coveredSpacePoint) {
            // TODO explanation
            $this->dbal->delete(self::TABLE_NAME_DOCUMENT_URIS, [
                'dimensionSpacePointHash' => $coveredSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
            ]);
            $this->dbal->insert(self::TABLE_NAME_DOCUMENT_URIS, [
                'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
                'uriPath' => $sourceData['uripath'],
                'nodePath' => $sourceData['nodepath'],
                'siteNodeName' => $sourceData['sitenodename'],
                'dimensionSpacePointHash' => $coveredSpacePoint->getHash(),
                'originDimensionSpacePointHash' => $targetOrigin->getHash(),
                'parentNodeAggregateIdentifier' => $sourceData['parentnodeaggregateidentifier'],
                'precedingNodeAggregateIdentifier' => $sourceData['precedingnodeaggregateidentifier'],
                'succeedingNodeAggregateIdentifier' => $sourceData['succeedingnodeaggregateidentifier'],
                'shortcutTarget' => $sourceData['shortcuttarget'],
            ]);
        }
    }

    public function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getAffectedDimensionSpacePoints() as $dimensionSpacePoint) {
            /** @var array $sourceData */
            $sourceData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :sourceDimensionSpacePointHash', [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                'sourceDimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
            ]);
            if ($sourceData === false) {
                // Probably not a document node
                continue;
            }
            $this->dbal->executeUpdate('UPDATE ' . self::TABLE_NAME_DOCUMENT_URIS . ' SET disabled = disabled + 1 WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodePath = :nodePath OR nodePath LIKE :childNodePathPrefix)', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodePath' => $sourceData['nodepath'],
                'childNodePathPrefix' => $sourceData['nodepath'] . '/%',
            ]);
        }
    }

    public function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getAffectedDimensionSpacePoints() as $dimensionSpacePoint) {
            /** @var array $sourceData */
            $sourceData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :sourceDimensionSpacePointHash', [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                'sourceDimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
            ]);
            if ($sourceData === false) {
                // Probably not a document node
                continue;
            }
            $this->dbal->executeUpdate('UPDATE ' . self::TABLE_NAME_DOCUMENT_URIS . ' SET disabled = disabled - 1 WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodePath = :nodePath OR nodePath LIKE :childNodePathPrefix)', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodePath' => $sourceData['nodepath'],
                'childNodePathPrefix' => $sourceData['nodepath'] . '/%',
            ]);
        }
    }

    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getAffectedCoveredDimensionSpacePoints() as $dimensionSpacePoint) {
            /** @var array $sourceData */
            $sourceData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :sourceDimensionSpacePointHash', [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                'sourceDimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
            ]);
            if ($sourceData === false) {
                // Probably not a document node
                continue;
            }

            $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                'succeedingNodeAggregateIdentifier' => $sourceData['succeedingnodeaggregateidentifier'],
            ], [
                'nodeAggregateIdentifier' => $sourceData['precedingnodeaggregateidentifier'],
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
            ]);
            $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                'precedingNodeAggregateIdentifier' => $sourceData['precedingnodeaggregateidentifier'],
            ], [
                'nodeAggregateIdentifier' => $sourceData['succeedingnodeaggregateidentifier'],
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
            ]);

            $this->dbal->executeUpdate('DELETE FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodePath = :nodePath OR nodePath LIKE :childNodePathPrefix)', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodePath' => $sourceData['nodepath'],
                'childNodePathPrefix' => $sourceData['nodepath'] . '/%',
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

        // TODO Can there be more affected dimension space points and how to determine them?

        /** @var array $sourceData */
        $sourceData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :sourceDimensionSpacePointHash', [
            'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
            'sourceDimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
        ]);

        if ($sourceData === false) {
            // probably not a document node
            return;
        }
        if (isset($newPropertyValues['targetMode']) || isset($newPropertyValues['target'])) {
            $shortcutTarget = isset($sourceData['shortcuttarget']) ? json_decode($sourceData['shortcuttarget'], true, 512, JSON_THROW_ON_ERROR) : [];
            $shortcutTarget = [
                'mode' => $newPropertyValues['targetMode'] ?? $shortcutTarget['mode'] ?? 'firstChildNode',
                'target' => $newPropertyValues['target'] ?? $shortcutTarget['target'] ?? null,
            ];
            $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                'shortcutTarget' => json_encode($shortcutTarget, JSON_THROW_ON_ERROR),
            ], [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                'dimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
            ]);
        }

        if (!isset($newPropertyValues['uriPathSegment']) || $sourceData['uripath'] === $newPropertyValues['uriPathSegment']) {
            return;
        }
        $oldUriPath = $sourceData['uripath'];
        // homepage -> TODO hacky?
        if ($oldUriPath === '') {
            return;
        }
        $uriPathSegments = explode('/', $oldUriPath);
        $uriPathSegments[array_key_last($uriPathSegments)] = $newPropertyValues['uriPathSegment'];
        $newUriPath = implode('/', $uriPathSegments);

        $this->dbal->executeUpdate(
            'UPDATE ' . self::TABLE_NAME_DOCUMENT_URIS . ' SET uriPath = CONCAT(:newUriPath, SUBSTRING(uriPath, LENGTH(:oldUriPath) + 1)) WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodePath = :nodePath OR nodePath LIKE :childNodePathPrefix)',
            [
                'newUriPath' => $newUriPath,
                'oldUriPath' => $oldUriPath,
                'dimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
                'nodePath' => $sourceData['nodepath'],
                'childNodePathPrefix' => $sourceData['nodepath'] . '/%',
            ]
        );
    }

    public function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamIdentifier())) {
            return;
        }
        foreach ($event->getNodeMoveMappings() as $moveMapping) {
            /** @var array $sourceNodeDatas */
            $sourceNodeDatas = $this->dbal->fetchAll('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier', [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
            ]);
            foreach ($sourceNodeDatas as $sourceNodeData) {
                $dimensionSpacePointHash = $sourceNodeData['dimensionspacepointhash'];

                // cut out node from old position
                if ($sourceNodeData['precedingnodeaggregateidentifier'] !== null) {
                    $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                        'succeedingNodeAggregateIdentifier' => $sourceNodeData['succeedingnodeaggregateidentifier'],
                    ], [
                        'nodeAggregateIdentifier' => $sourceNodeData['precedingnodeaggregateidentifier'],
                        'dimensionSpacePointHash' => $dimensionSpacePointHash,
                    ]);
                }
                if ($sourceNodeData['succeedingnodeaggregateidentifier'] !== null) {
                    $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                        'precedingNodeAggregateIdentifier' => $sourceNodeData['precedingnodeaggregateidentifier'],
                    ], [
                        'nodeAggregateIdentifier' => $sourceNodeData['succeedingnodeaggregateidentifier'],
                        'dimensionSpacePointHash' => $dimensionSpacePointHash,
                    ]);
                }

                // update new preceding and succeeding node (re-wire succeedingNodeAggregateIdentifier and precedingNodeAggregateIdentifier)
                $succeedingSiblingAssignment = $moveMapping->getNewSucceedingSiblingAssignments()->getAssignments()[$dimensionSpacePointHash] ?? null;
                $parentAssignment = $moveMapping->getNewParentAssignments()->getAssignments()[$dimensionSpacePointHash] ?? null;
                if ($succeedingSiblingAssignment !== null) {
                    /** @var array $newPrecedingNodeData */
                    $newPrecedingNodeData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE succeedingNodeAggregateIdentifier = :succeedingNodeAggregateIdentifier AND dimensionSpacePointHash = :dimensionSpacePointHash', [
                        'succeedingNodeAggregateIdentifier' => $succeedingSiblingAssignment->getNodeAggregateIdentifier(),
                        'dimensionSpacePointHash' => $dimensionSpacePointHash,
                    ]);

                    if (is_array($newPrecedingNodeData)) {
                        $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                            'succeedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                        ], [
                            'succeedingNodeAggregateIdentifier' => $succeedingSiblingAssignment->getNodeAggregateIdentifier(),
                            'dimensionSpacePointHash' => $dimensionSpacePointHash,
                        ]);
                        $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                            'precedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                        ], [
                            'precedingNodeAggregateIdentifier' => $newPrecedingNodeData['nodeaggregateidentifier'] ?? null,
                            'dimensionSpacePointHash' => $dimensionSpacePointHash,
                        ]);
                    } else {
                        $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                            'precedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                        ], [
                            'parentNodeAggregateIdentifier' => $parentAssignment !== null ? $parentAssignment->getNodeAggregateIdentifier() : $sourceNodeData['parentnodeaggregateidentifier'],
                            'precedingNodeAggregateIdentifier' => null,
                            'dimensionSpacePointHash' => $dimensionSpacePointHash,
                        ]);
                    }
                } else {
                    /** @var array $newPrecedingNodeData */
                    $newPrecedingNodeData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE parentNodeAggregateIdentifier = :parentNodeAggregateIdentifier AND dimensionSpacePointHash = :dimensionSpacePointHash AND succeedingNodeAggregateIdentifier IS NULL', [
                        'parentNodeAggregateIdentifier' => $sourceNodeData['parentnodeaggregateidentifier'],
                        'dimensionSpacePointHash' => $dimensionSpacePointHash,
                    ]);
                    $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                        'succeedingNodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                    ], [
                        'nodeAggregateIdentifier' => $newPrecedingNodeData['nodeaggregateidentifier'],
                        'dimensionSpacePointHash' => $dimensionSpacePointHash,
                    ]);
                }

                // TODO update disabled flag!

                if ($parentAssignment !== null) {
                    /** @var array $newParentNodeData */
                    $newParentNodeData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :sourceDimensionSpacePointHash', [
                        'nodeAggregateIdentifier' => $parentAssignment->getNodeAggregateIdentifier(),
                        'sourceDimensionSpacePointHash' => $dimensionSpacePointHash,
                    ]);
                    if ($newParentNodeData === false) {
                        // TODO (how) can this happen?
                        continue;
                    }

                    $sourceUriPathOffset = (int)strrpos($sourceNodeData['uripath'], '/');
                    $sourceNodePathOffset = (int)strrpos($sourceNodeData['nodepath'], '/');
                    $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                        'parentNodeAggregateIdentifier' => $parentAssignment->getNodeAggregateIdentifier(),
                        'nodePath' => empty($newParentNodeData['nodepath']) ? $sourceNodeData['nodeaggregateidentifier'] : $newParentNodeData['nodepath'] . '/' . $sourceNodeData['nodeaggregateidentifier'],
                        'uriPath' => trim($newParentNodeData['uripath'] . '/' . ltrim(substr($sourceNodeData['uripath'], $sourceUriPathOffset), '\/'), '\/'),
                        'succeedingNodeAggregateIdentifier' => $succeedingSiblingAssignment !== null ? $succeedingSiblingAssignment->getNodeAggregateIdentifier() : null,
                        'precedingNodeAggregateIdentifier' => $newPrecedingNodeData['nodeaggregateidentifier'] ?? null,
                    ], [
                        'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                        'dimensionSpacePointHash' => $dimensionSpacePointHash,
                    ]);
                    $this->dbal->executeUpdate('UPDATE
                        ' . self::TABLE_NAME_DOCUMENT_URIS . '
                        SET
                            nodePath = ' . (empty($newParentNodeData['nodepath']) ? 'SUBSTRING(nodePath, :sourceNodePathOffset)' : 'CONCAT(:newParentNodePath, \'/\', SUBSTRING(nodePath, :sourceNodePathOffset))') . ',
                            uriPath = ' . (empty($newParentNodeData['uripath']) ? 'SUBSTRING(uriPath, :sourceUriPathOffset)' : 'CONCAT(:newUriPath, \'/\', SUBSTRING(uriPath, :sourceUriPathOffset))') . '
                        WHERE
                            dimensionSpacePointHash = :dimensionSpacePointHash
                            AND nodePath LIKE :sourceNodePathPrefix
                        ', [
                        'newParentNodePath' => $newParentNodeData['nodepath'],
                        'sourceNodePathOffset' => $sourceNodePathOffset + 1,
                        'newUriPath' => $newParentNodeData['uripath'],
                        'sourceUriPathOffset' => $sourceUriPathOffset + 1,
                        'dimensionSpacePointHash' => $dimensionSpacePointHash,
                        'sourceNodePathPrefix' => $sourceNodeData['nodepath'] . '/%',
                    ]);
                } else {
                    $this->dbal->update(self::TABLE_NAME_DOCUMENT_URIS, [
                        'succeedingNodeAggregateIdentifier' => $succeedingSiblingAssignment !== null ? $succeedingSiblingAssignment->getNodeAggregateIdentifier() : null,
                        'precedingNodeAggregateIdentifier' => $newPrecedingNodeData['nodeaggregateidentifier'] ?? null,
                    ], [
                        'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                        'dimensionSpacePointHash' => $dimensionSpacePointHash,
                    ]);
                }
            }
        }
    }

    public function reset(): void
    {
        $this->dbal->exec('TRUNCATE ' . self::TABLE_NAME_DOCUMENT_URIS);
        $this->dbal->exec('TRUNCATE ' . self::TABLE_NAME_LIVE_CONTENT_STREAMS);
    }

    private function isLiveContentStream(ContentStreamIdentifier $contentStreamIdentifier)
    {
        if ($this->liveContentStreamIdentifierRuntimeCache === null) {
            $this->liveContentStreamIdentifierRuntimeCache = $this->dbal->fetchColumn('SELECT contentStreamIdentifier FROM ' . self::TABLE_NAME_LIVE_CONTENT_STREAMS);
        }
        return (string)$contentStreamIdentifier === $this->liveContentStreamIdentifierRuntimeCache;
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
}
