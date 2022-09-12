<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\NodeMoveMapping;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\EventStore\CatchUp\CatchUp;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStore\SetupResult;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;

/**
 * @implements ProjectionInterface<DocumentUriPathFinder>
 */
final class DocumentUriPathProjection implements ProjectionInterface
{
    public const COLUMN_TYPES_DOCUMENT_URIS = [
        'shortcutTarget' => Types::JSON,
    ];

    private DoctrineCheckpointStorage $checkpointStorage;
    private ?DocumentUriPathFinder $stateAccessor = null;

    public function __construct(
        private readonly EventNormalizer $eventNormalizer,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly Connection $dbal,
        private readonly string $tableNamePrefix,
    ) {
        $this->checkpointStorage = new DoctrineCheckpointStorage(
            $this->dbal,
            $this->tableNamePrefix . '_checkpoint',
            self::class
        );
    }

    public function setUp(): void
    {
        $this->setupTables();
        $this->checkpointStorage->setup();
    }

    private function setupTables(): SetupResult
    {
        $connection = $this->dbal;
        $schemaManager = $connection->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }

        $schema = (new DocumentUriPathSchemaBuilder($this->tableNamePrefix))->buildSchema();

        $schemaDiff = (new Comparator())->compare($schemaManager->createSchema(), $schema);
        foreach ($schemaDiff->toSaveSql($connection->getDatabasePlatform()) as $statement) {
            $connection->executeStatement($statement);
        }
        return SetupResult::success('');
    }


    public function reset(): void
    {
        $this->truncateDatabaseTables();
        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
    }

    private function truncateDatabaseTables(): void
    {
        try {
            $this->dbal->exec('TRUNCATE ' . $this->tableNamePrefix . '_uri');
            $this->dbal->exec('TRUNCATE ' . $this->tableNamePrefix . '_livecontentstreams');
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to truncate tables: %s', $e->getMessage()), 1599655382, $e);
        }
    }


    public function canHandle(Event $event): bool
    {
        $eventClassName = $this->eventNormalizer->getEventClassName($event);
        return in_array($eventClassName, [
            RootWorkspaceWasCreated::class,
            RootNodeAggregateWithNodeWasCreated::class,
            NodeAggregateWithNodeWasCreated::class,
            NodeAggregateTypeWasChanged::class,
            NodePeerVariantWasCreated::class,
            NodeGeneralizationVariantWasCreated::class,
            NodeSpecializationVariantWasCreated::class,
            NodeAggregateWasDisabled::class,
            NodeAggregateWasEnabled::class,
            NodeAggregateWasRemoved::class,
            NodePropertiesWereSet::class,
            NodeAggregateWasMoved::class,
            DimensionSpacePointWasMoved::class,
            DimensionShineThroughWasAdded::class,
        ]);
    }

    public function catchUp(EventStreamInterface $eventStream, ContentRepository $contentRepository): void
    {
        $catchUp = CatchUp::create($this->apply(...), $this->checkpointStorage);
        $catchUp->run($eventStream);
    }

    private function apply(\Neos\EventStore\Model\EventEnvelope $eventEnvelope): void
    {
        if (!$this->canHandle($eventEnvelope->event)) {
            return;
        }

        $eventInstance = $this->eventNormalizer->denormalize($eventEnvelope->event);

        $this->dbal->beginTransaction();

        // @codingStandardsIgnoreStart @phpstan-ignore-next-line
        match ($eventInstance::class) {
            RootWorkspaceWasCreated::class => $this->whenRootWorkspaceWasCreated($eventInstance),
            RootNodeAggregateWithNodeWasCreated::class => $this->whenRootNodeAggregateWithNodeWasCreated($eventInstance),
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($eventInstance),
            NodeAggregateTypeWasChanged::class => $this->whenNodeAggregateTypeWasChanged($eventInstance),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($eventInstance),
            NodeGeneralizationVariantWasCreated::class => $this->whenNodeGeneralizationVariantWasCreated($eventInstance),
            NodeSpecializationVariantWasCreated::class => $this->whenNodeSpecializationVariantWasCreated($eventInstance),
            NodeAggregateWasDisabled::class => $this->whenNodeAggregateWasDisabled($eventInstance),
            NodeAggregateWasEnabled::class => $this->whenNodeAggregateWasEnabled($eventInstance),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($eventInstance),
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($eventInstance, $eventEnvelope),
            NodeAggregateWasMoved::class => $this->whenNodeAggregateWasMoved($eventInstance),
            DimensionSpacePointWasMoved::class => $this->whenDimensionSpacePointWasMoved($eventInstance),
            DimensionShineThroughWasAdded::class => $this->whenDimensionShineThroughWasAdded($eventInstance),
        };
        // @codingStandardsIgnoreEnd

        try {
            $this->dbal->commit();
        } catch (ConnectionException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to commit transaction in %s: %s',
                __METHOD__,
                $e->getMessage()
            ), 1599580555, $e);
        }
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->checkpointStorage->getHighestAppliedSequenceNumber();
    }

    public function getState(): DocumentUriPathFinder
    {
        if (!$this->stateAccessor) {
            $this->stateAccessor = new DocumentUriPathFinder($this->dbal, $this->tableNamePrefix);
        }
        return $this->stateAccessor;
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        try {
            $this->dbal->insert($this->tableNamePrefix . '_livecontentstreams', [
                'contentStreamId' => $event->newContentStreamId,
                'workspaceName' => $event->workspaceName,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to insert root content stream id of the root workspace "%s": %s',
                $event->workspaceName,
                $e->getMessage()
            ), 1599646608, $e);
        }
    }

    private function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        foreach ($event->coveredDimensionSpacePoints as $dimensionSpacePoint) {
            $this->insertNode([
                'uriPath' => '',
                'nodeAggregateIdPath' => $event->nodeAggregateId,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateId' => $event->nodeAggregateId,
            ]);
        }
    }

    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        if (!$this->isDocumentNodeType($event->nodeTypeName)) {
            return;
        }

        $propertyValues = $event->initialPropertyValues->getPlainValues();
        $uriPathSegment = $propertyValues['uriPathSegment'] ?? '';

        $shortcutTarget = null;
        if ($this->isShortcutNodeType($event->nodeTypeName)) {
            $shortcutTarget = [
                'mode' => $propertyValues['targetMode'] ?? 'firstChildNode',
                'target' => $propertyValues['target'] ?? null,
            ];
        }

        foreach ($event->coveredDimensionSpacePoints as $dimensionSpacePoint) {
            $parentNode = $this->tryGetNode(fn () => $this->getState()->getByIdAndDimensionSpacePointHash(
                $event->parentNodeAggregateId,
                $dimensionSpacePoint->hash
            ));
            if ($parentNode === null) {
                // this should not happen
                continue;
            }
            /** @var DocumentNodeInfo|null $precedingNode */
            $precedingNode = null;

            if ($event->succeedingNodeAggregateId === null) {
                $precedingNode = $this->tryGetNode(fn () => $this->getState()->getLastChildNode(
                    $parentNode->getNodeAggregateId(),
                    $dimensionSpacePoint->hash
                ));
                if ($precedingNode !== null) {
                    // make the new node the new succeeding node of the previously last child
                    // (= insert at the end of all children)
                    $this->updateNode($precedingNode, [
                        'succeedingNodeAggregateId' => $event->nodeAggregateId
                    ]);
                }
            } else {
                $precedingNode = $this->tryGetNode(fn () => $this->getState()->getPrecedingNode(
                    $event->succeedingNodeAggregateId,
                    $parentNode->getNodeAggregateId(),
                    $dimensionSpacePoint->hash
                ));
                if ($precedingNode !== null) {
                    // make the new node the new succeeding node of the previously preceding node
                    // of the specified succeeding node (= re-wire <preceding>-<succeeding> to <preceding>-<new node>)
                    $this->updateNode($precedingNode, [
                        'succeedingNodeAggregateId' => $event->nodeAggregateId
                    ]);
                }
                $this->updateNodeByIdAndDimensionSpacePointHash(
                    $event->succeedingNodeAggregateId,
                    $dimensionSpacePoint->hash,
                    ['precedingNodeAggregateId' => $event->nodeAggregateId]
                );
            }

            $nodeAggregateIdPath = $parentNode->getNodeAggregateIdPath()
                . '/' . $event->nodeAggregateId;
            if ($parentNode->isRoot() && $event->nodeName !== null) {
                $uriPath = '';
                $siteNodeName = SiteNodeName::fromNodeName($event->nodeName);
            } else {
                $uriPath = $parentNode->getUriPath() === ''
                    ? $uriPathSegment
                    : $parentNode->getUriPath() . '/' . $uriPathSegment;
                $siteNodeName = $parentNode->getSiteNodeName();
            }
            $this->insertNode([
                'nodeAggregateId' => $event->nodeAggregateId,
                'uriPath' => $uriPath,
                'nodeAggregateIdPath' => $nodeAggregateIdPath,
                'siteNodeName' => $siteNodeName,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'originDimensionSpacePointHash' => $event->originDimensionSpacePoint->hash,
                'parentNodeAggregateId' => $parentNode->getNodeAggregateId(),
                'precedingNodeAggregateId' => $precedingNode?->getNodeAggregateId(),
                'succeedingNodeAggregateId' => $event->succeedingNodeAggregateId,
                'shortcutTarget' => $shortcutTarget,
            ]);
        }
    }

    private function whenNodeAggregateTypeWasChanged(NodeAggregateTypeWasChanged $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        if ($this->isShortcutNodeType($event->newNodeTypeName)) {
            // The node has been turned into a shortcut node, but since the shortcut mode is not yet set
            // we'll set it to "firstChildNode" in order to prevent an invalid mode
            $this->updateNodeQuery('SET shortcuttarget = \'{"mode":"firstChildNode","target":null}\'
                WHERE nodeAggregateId = :nodeAggregateId
                    AND shortcuttarget IS NULL', [
                'nodeAggregateId' => $event->nodeAggregateId,
            ]);
        } elseif ($this->isDocumentNodeType($event->newNodeTypeName)) {
            $this->updateNodeQuery('SET shortcuttarget = NULL
                WHERE nodeAggregateId = :nodeAggregateId
                    AND shortcuttarget IS NOT NULL', [
                'nodeAggregateId' => $event->nodeAggregateId,
            ]);
        }
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        $this->copyVariants(
            $event->nodeAggregateId,
            $event->sourceOrigin,
            $event->peerOrigin,
            $event->peerCoverage
        );
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        $this->copyVariants(
            $event->nodeAggregateId,
            $event->sourceOrigin,
            $event->generalizationOrigin,
            $event->generalizationCoverage
        );
    }

    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        $this->copyVariants(
            $event->nodeAggregateId,
            $event->sourceOrigin,
            $event->specializationOrigin,
            $event->specializationCoverage
        );
    }

    private function copyVariants(
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        DimensionSpacePointSet $coveredSpacePoints
    ): void {
        $sourceNode = $this->tryGetNode(fn () => $this->getState()->getByIdAndDimensionSpacePointHash(
            $nodeAggregateId,
            $sourceOrigin->hash
        ));
        if ($sourceNode === null) {
            // Probably not a document node
            return;
        }
        foreach ($coveredSpacePoints as $coveredSpacePoint) {
            // Especially when importing a site it can happen that variants are created in a "non-deterministic" order,
            // so we need to first make sure a target variant doesn't exist:
            $this->deleteNodeByIdAndDimensionSpacePointHash($nodeAggregateId, $coveredSpacePoint->hash);

            $this->insertNode(
                $sourceNode
                ->withDimensionSpacePoint($coveredSpacePoint)
                ->withOriginDimensionSpacePoint($targetOrigin)
                ->toArray()
            );
        }
    }

    private function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $node = $this->tryGetNode(fn () => $this->getState()->getByIdAndDimensionSpacePointHash(
                $event->nodeAggregateId,
                $dimensionSpacePoint->hash
            ));
            if ($node === null) {
                // Probably not a document node
                continue;
            }
            # node is already explicitly disabled
            if ($this->isNodeExplicitlyDisabled($node)) {
                return;
            }
            $this->updateNodeQuery('SET disabled = disabled + 1
                    WHERE dimensionSpacePointHash = :dimensionSpacePointHash
                        AND (
                            nodeAggregateId = :nodeAggregateId
                            OR nodeAggregateIdPath LIKE :childNodeAggregateIdPathPrefix
                        )', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateId' => $event->nodeAggregateId,
                'childNodeAggregateIdPathPrefix' => $node->getNodeAggregateIdPath() . '/%',
            ]);
        }
    }

    private function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $node = $this->tryGetNode(fn () => $this->getState()->getByIdAndDimensionSpacePointHash(
                $event->nodeAggregateId,
                $dimensionSpacePoint->hash
            ));
            if ($node === null) {
                // Probably not a document node
                continue;
            }
            # node is not explicitly disabled, so we must not re-enable it
            if (!$this->isNodeExplicitlyDisabled($node)) {
                return;
            }
            $this->updateNodeQuery('SET disabled = disabled - 1
                WHERE dimensionSpacePointHash = :dimensionSpacePointHash
                    AND (
                        nodeAggregateId = :nodeAggregateId
                        OR nodeAggregateIdPath LIKE :childNodeAggregateIdPathPrefix
                    )', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateId' => $node->getNodeAggregateId(),
                'childNodeAggregateIdPathPrefix' => $node->getNodeAggregateIdPath() . '/%',
            ]);
        }
    }

    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        foreach ($event->affectedCoveredDimensionSpacePoints as $dimensionSpacePoint) {
            $node = $this->tryGetNode(fn () => $this->getState()->getByIdAndDimensionSpacePointHash(
                $event->nodeAggregateId,
                $dimensionSpacePoint->hash
            ));
            if ($node === null) {
                // Probably not a document node
                continue;
            }

            $this->disconnectNodeFromSiblings($node);

            $this->deleteNodeQuery('WHERE dimensionSpacePointHash = :dimensionSpacePointHash
                    AND (
                        nodeAggregateId = :nodeAggregateId
                        OR nodeAggregateIdPath LIKE :childNodeAggregateIdPathPrefix
                    )', [
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateId' => $node->getNodeAggregateId(),
                'childNodeAggregateIdPathPrefix' => $node->getNodeAggregateIdPath() . '/%',
            ]);
        }
    }

    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }
        $newPropertyValues = $event->propertyValues->getPlainValues();
        if (
            !isset($newPropertyValues['uriPathSegment'])
            && !isset($newPropertyValues['targetMode'])
            && !isset($newPropertyValues['target'])
        ) {
            return;
        }

        // TODO Can there be more affected dimension space points and how to determine them?
        // see https://github.com/neos/contentrepository-development-collection/issues/163

        $node = $this->tryGetNode(fn () => $this->getState()->getByIdAndDimensionSpacePointHash(
            $event->nodeAggregateId,
            $event->originDimensionSpacePoint->hash
        ));

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
            $this->updateNodeByIdAndDimensionSpacePointHash(
                $event->nodeAggregateId,
                $event->originDimensionSpacePoint->hash,
                ['shortcutTarget' => $shortcutTarget]
            );
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

        $this->updateNodeQuery(
            'SET uriPath = CONCAT(:newUriPath, SUBSTRING(uriPath, LENGTH(:oldUriPath) + 1))
                WHERE dimensionSpacePointHash = :dimensionSpacePointHash
                    AND (
                        nodeAggregateId = :nodeAggregateId
                        OR nodeAggregateIdPath LIKE :childNodeAggregateIdPathPrefix
                    )',
            [
                'newUriPath' => $newUriPath,
                'oldUriPath' => $oldUriPath,
                'dimensionSpacePointHash' => $event->originDimensionSpacePoint->hash,
                'nodeAggregateId' => $node->getNodeAggregateId(),
                'childNodeAggregateIdPathPrefix' => $node->getNodeAggregateIdPath() . '/%',
            ]
        );
        $this->emitDocumentUriPathChanged($oldUriPath, $newUriPath, $event, $eventEnvelope);
    }

    private function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        if (!$this->isLiveContentStream($event->getContentStreamId())) {
            return;
        }
        if (!is_null($event->nodeMoveMappings)) {
            foreach ($event->nodeMoveMappings as $moveMapping) {
                /* @var \Neos\ContentRepository\Core\Feature\NodeMove\Dto\NodeMoveMapping $moveMapping */
                foreach (
                    $this->getState()->getNodeVariantsById(
                        $event->getNodeAggregateId()
                    ) as $node
                ) {
                    $parentAssignment = $moveMapping->newParentAssignments
                            ->getAssignments()[$node->getDimensionSpacePointHash()] ?? null;
                    $newParentNodeAggregateId = $parentAssignment !== null
                        ? $parentAssignment->nodeAggregateId
                        : $node->getParentNodeAggregateId();

                    $succeedingSiblingAssignment = $moveMapping->newSucceedingSiblingAssignments
                            ->getAssignments()[$node->getDimensionSpacePointHash()] ?? null;
                    $newSucceedingNodeAggregateId = $succeedingSiblingAssignment?->nodeAggregateId;

                    $this->moveNode($node, $newParentNodeAggregateId, $newSucceedingNodeAggregateId);
                }
            }
        } else {
            // @todo do something else
        }
    }

    private function moveNode(
        DocumentNodeInfo $node,
        NodeAggregateId $newParentNodeAggregateId,
        ?NodeAggregateId $newSucceedingNodeAggregateId
    ): void {
        $this->disconnectNodeFromSiblings($node);

        $this->connectNodeWithSiblings($node, $newParentNodeAggregateId, $newSucceedingNodeAggregateId);

        if ($newParentNodeAggregateId->equals($node->getParentNodeAggregateId())) {
            return;
        }
        $newParentNode = $this->tryGetNode(fn () => $this->getState()->getByIdAndDimensionSpacePointHash(
            $newParentNodeAggregateId,
            $node->getDimensionSpacePointHash()
        ));
        if ($newParentNode === null) {
            // This should never happen really..
            return;
        }

        $disabledDelta = $newParentNode->getDisableLevel() - $node->getDisableLevel();
        if ($this->isNodeExplicitlyDisabled($node)) {
            $disabledDelta++;
        }
        $this->updateNodeQuery(
            /** @codingStandardsIgnoreStart */
            'SET
                nodeAggregateIdPath = TRIM(TRAILING "/" FROM CONCAT(:newParentNodeAggregateIdPath, "/", TRIM(LEADING "/" FROM SUBSTRING(nodeAggregateIdPath, :sourceNodeAggregateIdPathOffset)))),
                uriPath = TRIM("/" FROM CONCAT(:newParentUriPath, "/", TRIM(LEADING "/" FROM SUBSTRING(uriPath, :sourceUriPathOffset)))),
                disabled = disabled + ' . $disabledDelta . '
            WHERE
                dimensionSpacePointHash = :dimensionSpacePointHash
                    AND (nodeAggregateId = :nodeAggregateId
                    OR nodeAggregateIdPath LIKE :childNodeAggregateIdPathPrefix)
            ',
            /** @codingStandardsIgnoreEnd */
            [
                'nodeAggregateId' => $node->getNodeAggregateId(),
                'newParentNodeAggregateIdPath' => $newParentNode->getNodeAggregateIdPath(),
                'sourceNodeAggregateIdPathOffset'
                    => (int)strrpos($node->getNodeAggregateIdPath(), '/') + 1,
                'newParentUriPath' => $newParentNode->getUriPath(),
                'sourceUriPathOffset' => (int)strrpos($node->getUriPath(), '/') + 1,
                'dimensionSpacePointHash' => $node->getDimensionSpacePointHash(),
                'childNodeAggregateIdPathPrefix' => $node->getNodeAggregateIdPath() . '/%',
            ]
        );
    }

    private function isNodeExplicitlyDisabled(DocumentNodeInfo $node): bool
    {
        if (!$node->isDisabled()) {
            return false;
        }
        $parentNode = $this->tryGetNode(fn () => $this->getState()->getByIdAndDimensionSpacePointHash(
            $node->getParentNodeAggregateId(),
            $node->getDimensionSpacePointHash()
        ));
        $parentDisabledLevel = $parentNode !== null ? $parentNode->getDisableLevel() : 0;
        return $node->getDisableLevel() - $parentDisabledLevel !== 0;
    }

    private function isDocumentNodeType(NodeTypeName $nodeTypeName): bool
    {
        // HACK: We consider the currently configured node type of the given node.
        // This is a deliberate side effect of this projector!
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->getValue());
        return $nodeType->isOfType('Neos.Neos:Document');
    }

    private function isShortcutNodeType(NodeTypeName $nodeTypeName): bool
    {
        // HACK: We consider the currently configured node type of the given node.
        // This is a deliberate side effect of this projector!
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->getValue());
        return $nodeType->isOfType('Neos.Neos:Shortcut');
    }

    private function isLiveContentStream(ContentStreamId $contentStreamId): bool
    {
        return $contentStreamId->equals($this->getState()->getLiveContentStreamId());
    }

    private function tryGetNode(\Closure $closure): ?DocumentNodeInfo
    {
        try {
            return $closure();
        } catch (NodeNotFoundException $_) {
            /** @noinspection BadExceptionsProcessingInspection,PhpRedundantCatchClauseInspection */
            return null;
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private function insertNode(array $data): void
    {
        try {
            $this->dbal->insert($this->tableNamePrefix . '_uri', $data, self::COLUMN_TYPES_DOCUMENT_URIS);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to insert node: %s', $e->getMessage()), 1599646694, $e);
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private function updateNode(DocumentNodeInfo $node, array $data): void
    {
        $this->updateNodeByIdAndDimensionSpacePointHash(
            $node->getNodeAggregateId(),
            $node->getDimensionSpacePointHash(),
            $data
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    private function updateNodeByIdAndDimensionSpacePointHash(
        NodeAggregateId $nodeAggregateId,
        string $dimensionSpacePointHash,
        array $data
    ): void {
        try {
            $this->dbal->update(
                $this->tableNamePrefix . '_uri',
                $data,
                compact('nodeAggregateId', 'dimensionSpacePointHash'),
                self::COLUMN_TYPES_DOCUMENT_URIS
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to update node "%s": %s',
                $nodeAggregateId,
                $e->getMessage()
            ), 1599646777, $e);
        }
    }

    /**
     * @param array<string,mixed> $parameters
     */
    private function updateNodeQuery(string $query, array $parameters): void
    {
        try {
            $this->dbal->executeQuery(
                'UPDATE ' . $this->tableNamePrefix . '_uri ' . $query,
                $parameters,
                self::COLUMN_TYPES_DOCUMENT_URIS
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to update node via custom query: %s',
                $e->getMessage()
            ), 1599659170, $e);
        }
    }

    private function deleteNodeByIdAndDimensionSpacePointHash(
        NodeAggregateId $nodeAggregateId,
        string $dimensionSpacePointHash
    ): void {
        try {
            $this->dbal->delete(
                $this->tableNamePrefix . '_uri',
                compact('nodeAggregateId', 'dimensionSpacePointHash'),
                self::COLUMN_TYPES_DOCUMENT_URIS
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to delete node "%s": %s',
                $nodeAggregateId,
                $e->getMessage()
            ), 1599655284, $e);
        }
    }

    /**
     * @param array<string,mixed> $parameters
     */
    private function deleteNodeQuery(string $query, array $parameters): void
    {
        try {
            $this->dbal->executeQuery(
                'DELETE FROM ' . $this->tableNamePrefix . '_uri ' . $query,
                $parameters,
                self::COLUMN_TYPES_DOCUMENT_URIS
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to delete node via custom query: %s',
                $e->getMessage()
            ), 1599659226, $e);
        }
    }

    private function disconnectNodeFromSiblings(DocumentNodeInfo $node): void
    {
        if ($node->hasPrecedingNodeAggregateId()) {
            $this->updateNodeByIdAndDimensionSpacePointHash(
                $node->getPrecedingNodeAggregateId(),
                $node->getDimensionSpacePointHash(),
                ['succeedingNodeAggregateId' =>
                    $node->hasSucceedingNodeAggregateId() ? $node->getSucceedingNodeAggregateId() : null
                ]
            );
        }
        if ($node->hasSucceedingNodeAggregateId()) {
            $this->updateNodeByIdAndDimensionSpacePointHash(
                $node->getSucceedingNodeAggregateId(),
                $node->getDimensionSpacePointHash(),
                ['precedingNodeAggregateId' =>
                    $node->hasPrecedingNodeAggregateId() ? $node->getPrecedingNodeAggregateId() : null
                ]
            );
        }
    }

    private function connectNodeWithSiblings(
        DocumentNodeInfo $node,
        NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $newSucceedingNodeAggregateId
    ): void {
        if ($newSucceedingNodeAggregateId !== null) {
            $newPrecedingNode = $this->tryGetNode(fn () => $this->getState()->getPrecedingNode(
                $newSucceedingNodeAggregateId,
                $parentNodeAggregateId,
                $node->getDimensionSpacePointHash()
            ));

            // update new succeeding node
            $this->updateNodeByIdAndDimensionSpacePointHash(
                $newSucceedingNodeAggregateId,
                $node->getDimensionSpacePointHash(),
                ['precedingNodeAggregateId' => $node->getNodeAggregateId()]
            );
        } else {
            $newPrecedingNode = $this->tryGetNode(fn () => $this->getState()->getLastChildNode(
                $parentNodeAggregateId,
                $node->getDimensionSpacePointHash()
            ));
        }
        if ($newPrecedingNode !== null) {
            $this->updateNode(
                $newPrecedingNode,
                ['succeedingNodeAggregateId' => $node->getNodeAggregateId()]
            );
        }

        // update node itself
        $this->updateNode($node, [
            'parentNodeAggregateId' => $parentNodeAggregateId,
            'precedingNodeAggregateId' => $newPrecedingNode?->getNodeAggregateId(),
            'succeedingNodeAggregateId' => $newSucceedingNodeAggregateId,
        ]);
    }


    private function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        if ($this->isLiveContentStream($event->contentStreamId)) {
            $this->updateNodeQuery(
                'SET dimensionspacepointhash = :newDimensionSpacePointHash
                        WHERE dimensionspacepointhash = :originalDimensionSpacePointHash',
                [
                    'originalDimensionSpacePointHash' => $event->source->hash,
                    'newDimensionSpacePointHash' => $event->target->hash,
                ]
            );

            $this->updateNodeQuery(
                'SET origindimensionspacepointhash = :newDimensionSpacePointHash
                        WHERE origindimensionspacepointhash = :originalDimensionSpacePointHash',
                [
                    'originalDimensionSpacePointHash' => $event->source->hash,
                    'newDimensionSpacePointHash' => $event->target->hash,
                ]
            );
        }
    }


    private function whenDimensionShineThroughWasAdded(DimensionShineThroughWasAdded $event): void
    {
        if ($this->isLiveContentStream($event->contentStreamId)) {
            try {
                $this->dbal->executeStatement('INSERT INTO ' . $this->tableNamePrefix . '_uri (
                    nodeaggregateid,
                    uripath,
                    nodeaggregateidpath,
                    sitenodename,
                    disabled,
                    dimensionspacepointhash,
                    origindimensionspacepointhash,
                    parentnodeaggregateid,
                    precedingnodeaggregateid,
                    succeedingnodeaggregateid,
                    shortcuttarget
                )
                SELECT
                    nodeaggregateid,
                    uripath,
                    nodeaggregateidpath,
                    sitenodename,
                    disabled,
                    :newDimensionSpacePointHash AS dimensionspacepointhash,
                    origindimensionspacepointhash,
                    parentnodeaggregateid,
                    precedingnodeaggregateid,
                    succeedingnodeaggregateid,
                    shortcuttarget
                FROM
                    ' . $this->tableNamePrefix . '_uri
                WHERE
                    dimensionSpacePointHash = :sourceDimensionSpacePointHash
                ', [
                    'sourceDimensionSpacePointHash' => $event->source->hash,
                    'newDimensionSpacePointHash' => $event->target->hash,
                ]);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf(
                    'Failed to insert new dimension shine through: %s',
                    $e->getMessage()
                ), 1599646608, $e);
            }
        }
    }

    /**
     * @Flow\Signal
     */
    public function emitDocumentUriPathChanged(
        string $oldUriPath,
        string $newUriPath,
        NodePropertiesWereSet $event,
        EventEnvelope $eventEnvelope
    ): void {
    }
}
