<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
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
class DocumentUriPathProjector implements ProjectorInterface, BeforeInvokeInterface, AfterInvokeInterface
{
    public const TABLE_NAME_DOCUMENT_URIS = 'neos_neos_projection_document_uri';
    public const TABLE_NAME_LIVE_CONTENT_STREAMS = 'neos_neos_projection_document_uri_livecontentstreams';


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
        if ((string)$event->getContentStreamIdentifier() !== $this->getLiveContentStreamIdentifier()) {
            return;
        }
        $uriPathSegment = $event->getInitialPropertyValues()->getPlainValues()['uriPathSegment'] ?? null;
        if ($uriPathSegment === null) {
            return;
        }

        foreach ($event->getCoveredDimensionSpacePoints() as $dimensionSpacePoint) {
            /** @var array $parentNodePathInfo */
            $parentNodePathInfo = $this->dbal->fetchAssoc('SELECT uriPath, nodePath FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND nodeAggregateIdentifier = :nodeAggregateIdentifier LIMIT 1', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $event->getParentNodeAggregateIdentifier(),
            ]);

            // TODO reset uri path if parent node === root node
            // TODO keep site node

            $nodeName = $event->getNodeName() !== null ? (string)$event->getNodeName() : (string)$event->getNodeAggregateIdentifier();
            $nodePath = ($parentNodePathInfo !== false ? $parentNodePathInfo['nodePath'] . '/' : '') . $nodeName;
            if ($parentNodePathInfo === false || $parentNodePathInfo['nodePath'] === '') {
                $uriPath = '';
            } else {
                $uriPath = $parentNodePathInfo['uriPath'] === '' ? $uriPathSegment : $parentNodePathInfo['uriPath'] . '/' . $uriPathSegment;
            }
            $this->dbal->insert(self::TABLE_NAME_DOCUMENT_URIS, [
                'uriPath' => $uriPath,
                'nodePath' => $nodePath,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                'originDimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
            ]);
        }
    }

    public function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $this->copyVariants($event->getNodeAggregateIdentifier(), $event->getSourceOrigin(), $event->getPeerOrigin(), $event->getPeerCoverage());
    }

    public function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $this->copyVariants($event->getNodeAggregateIdentifier(), $event->getSourceOrigin(), $event->getGeneralizationOrigin(), $event->getGeneralizationCoverage());
    }

    public function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
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
            $this->dbal->insert(self::TABLE_NAME_DOCUMENT_URIS, [
                'uriPath' => $sourceData['uripath'],
                'nodePath' => $sourceData['nodepath'],
                'dimensionSpacePointHash' => $coveredSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
                'originDimensionSpacePointHash' => $targetOrigin->getHash(),
            ]);
        }
    }

    public function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        if ((string)$event->getContentStreamIdentifier() !== $this->getLiveContentStreamIdentifier()) {
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
        if ((string)$event->getContentStreamIdentifier() !== $this->getLiveContentStreamIdentifier()) {
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
        if ((string)$event->getContentStreamIdentifier() !== $this->getLiveContentStreamIdentifier()) {
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
            $this->dbal->executeUpdate('DELETE FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodePath = :nodePath OR nodePath LIKE :childNodePathPrefix)', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodePath' => $sourceData['nodepath'],
                'childNodePathPrefix' => $sourceData['nodepath'] . '/%',
            ]);
        }
    }

    public function whenNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {
        if ((string)$event->getContentStreamIdentifier() !== $this->getLiveContentStreamIdentifier()) {
            return;
        }

        $newUriPathSegment = $event->getPropertyValues()->getPlainValues()['uriPathSegment'] ?? null;
        if ($newUriPathSegment === null) {
            return;
        }

        // TODO Can there be more affected dimension space points and how to determine them?

        /** @var array $sourceData */
        $sourceData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :sourceDimensionSpacePointHash', [
            'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
            'sourceDimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
        ]);
        if ($sourceData === false || $sourceData['uripath'] === $newUriPathSegment) {
            return;
        }
        $oldUriPath = $sourceData['uripath'];
        // homepage -> TODO hacky?
        if ($oldUriPath === '') {
            return;
        }
        $uriPathSegments = explode('/', $oldUriPath);
        $uriPathSegments[array_key_last($uriPathSegments)] = $newUriPathSegment;
        $newUriPath = implode('/', $uriPathSegments);

        $this->dbal->executeUpdate('UPDATE ' . self::TABLE_NAME_DOCUMENT_URIS . ' SET uriPath = CONCAT(:newUriPath, SUBSTRING(uriPath, LENGTH(:oldUriPath) + 1)) WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND (nodePath = :nodePath OR nodePath LIKE :childNodePathPrefix)', [
            'newUriPath' => $newUriPath,
            'oldUriPath' => $oldUriPath,
            'dimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
            'nodePath' => $sourceData['nodepath'],
            'childNodePathPrefix' => $sourceData['nodepath'] . '/%',
        ]);
    }

    public function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        if ((string)$event->getContentStreamIdentifier() !== $this->getLiveContentStreamIdentifier()) {
            return;
        }

        foreach ($event->getNodeMoveMappings() as $moveMapping) {
            /** @var array $sourceData */
            $sourceData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :sourceDimensionSpacePointHash', [
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
                'sourceDimensionSpacePointHash' => $moveMapping->getMovedNodeOrigin()->getHash(),
            ]);
            if ($sourceData === false) {
                // probably not a document node that was moved
                continue;
            }
            $sourceUriPathOffset = strrpos($sourceData['uripath'], '/');
            $sourceUriPathOffset = $sourceUriPathOffset === false ? 1 : $sourceUriPathOffset + 2;
            $sourceNodePathOffset = strrpos($sourceData['nodepath'], '/');
            $sourceNodePathOffset = $sourceNodePathOffset === false ? 1 : $sourceNodePathOffset + 2;

            foreach ($moveMapping->getNewParentAssignments() as $parentAssignment) {
                /** @var array $newParentNodeData */
                $newParentNodeData = $this->dbal->fetchAssoc('SELECT * FROM ' . self::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :sourceDimensionSpacePointHash', [
                    'nodeAggregateIdentifier' => $parentAssignment->getNodeAggregateIdentifier(),
                    'sourceDimensionSpacePointHash' => $parentAssignment->getOriginDimensionSpacePoint()->getHash(),
                ]);
                if ($newParentNodeData === false) {
                    // TODO (how) can this happen?
                    continue;
                }

                // TODO update disabled flag!

                $this->dbal->executeUpdate('UPDATE
                        ' . self::TABLE_NAME_DOCUMENT_URIS . '
                    SET
                        nodePath = CONCAT(:newParentNodePath, \'/\', SUBSTRING(nodePath, :sourceNodePathOffset)),
                        uriPath = CONCAT(:newUriPath, \'/\', SUBSTRING(uriPath, :sourceUriPathOffset))
                    WHERE
                        dimensionSpacePointHash = :dimensionSpacePointHash
                        AND (nodePath = :sourceNodePath OR nodePath LIKE :sourceNodePathPrefix)
                    ', [
                    'newParentNodePath' => $newParentNodeData['nodepath'],
                    'sourceNodePathOffset' => $sourceNodePathOffset,
                    'newUriPath' => $newParentNodeData['uripath'],
                    'sourceUriPathOffset' => $sourceUriPathOffset,
                    'dimensionSpacePointHash' => $parentAssignment->getOriginDimensionSpacePoint()->getHash(),
                    'sourceNodePath' => $sourceData['nodepath'],
                    'sourceNodePathPrefix' => $sourceData['nodepath'] . '/%',
                ]);
            }
        }
    }

    public function reset(): void
    {
        $this->dbal->exec('TRUNCATE ' . self::TABLE_NAME_DOCUMENT_URIS);
        $this->dbal->exec('TRUNCATE ' . self::TABLE_NAME_LIVE_CONTENT_STREAMS);
    }

    private function getLiveContentStreamIdentifier(): string
    {
        if ($this->liveContentStreamIdentifierRuntimeCache === null) {
            $this->liveContentStreamIdentifierRuntimeCache = $this->dbal->fetchColumn('SELECT contentStreamIdentifier FROM ' . self::TABLE_NAME_LIVE_CONTENT_STREAMS);
        }
        return $this->liveContentStreamIdentifierRuntimeCache;
    }
}
