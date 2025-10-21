<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\PendingChangesProjection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;

/**
 * @internal Only for consumption inside Neos. Not public api because the implementation will be refactored sooner or later: https://github.com/neos/neos-development-collection/issues/5493
 * @implements ProjectionInterface<ChangeFinder>
 */
class ChangeProjection implements ProjectionInterface
{
    private ChangeFinder $changeFinder;

    public function __construct(
        private readonly Connection $dbal,
        private readonly string $tableNamePrefix,
    ) {
        $this->changeFinder = new ChangeFinder($this->dbal, $this->tableNamePrefix);
    }

    /**
     * @return void
     * @throws DBALException
     */
    public function setUp(): void
    {
        foreach ($this->determineRequiredSqlStatements() as $statement) {
            $this->dbal->executeStatement($statement);
        }
    }

    public function status(): ProjectionStatus
    {
        try {
            $this->dbal->connect();
        } catch (\Throwable $e) {
            return ProjectionStatus::error(sprintf('Failed to connect to database: %s', $e->getMessage()));
        }
        try {
            $requiredSqlStatements = $this->determineRequiredSqlStatements();
        } catch (\Throwable $e) {
            return ProjectionStatus::error(sprintf('Failed to determine required SQL statements: %s', $e->getMessage()));
        }
        if ($requiredSqlStatements !== []) {
            return ProjectionStatus::setupRequired(sprintf('The following SQL statement%s required: %s', count($requiredSqlStatements) !== 1 ? 's are' : ' is', implode(chr(10), $requiredSqlStatements)));
        }
        return ProjectionStatus::ok();
    }

    /**
     * @return array<string>
     * @throws DBALException
     * @throws SchemaException
     */
    private function determineRequiredSqlStatements(): array
    {
        $connection = $this->dbal;
        $platform = $this->dbal->getDatabasePlatform();

        $changeTable = new Table($this->tableNamePrefix, [
            DbalSchemaFactory::columnForContentStreamId('contentStreamId', $platform)->setNotNull(true),
            (new Column('created', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            (new Column('changed', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            (new Column('moved', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            DbalSchemaFactory::columnForNodeAggregateId('nodeAggregateId', $platform)->setNotnull(true),
            DbalSchemaFactory::columnForDimensionSpacePoint('originDimensionSpacePoint', $platform)->setNotnull(false),
            DbalSchemaFactory::columnForDimensionSpacePointHash('originDimensionSpacePointHash', $platform)->setNotnull(true),
            (new Column('deleted', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            // Despite the name suggesting this might be an anchor point of sorts, this is a nodeAggregateId type
            DbalSchemaFactory::columnForNodeAggregateId('removalAttachmentPoint', $platform)->setNotnull(false)
        ]);

        $changeTable->setPrimaryKey([
            'contentStreamId',
            'nodeAggregateId',
            'originDimensionSpacePointHash'
        ]);

        $schema = DbalSchemaFactory::createSchemaWithTables($connection, [$changeTable]);
        $statements = DbalSchemaDiff::determineRequiredSqlStatements($connection, $schema);

        return $statements;
    }

    public function resetState(): void
    {
        $this->dbal->exec('TRUNCATE ' . $this->tableNamePrefix);
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            NodeAggregateWasMoved::class => $this->whenNodeAggregateWasMoved($event),
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($event),
            NodeReferencesWereSet::class => $this->whenNodeReferencesWereSet($event),
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($event),
            SubtreeWasTagged::class => $this->whenSubtreeWasTagged($event),
            SubtreeWasUntagged::class => $this->whenSubtreeWasUntagged($event),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($event),
            NodeSpecializationVariantWasCreated::class => $this->whenNodeSpecializationVariantWasCreated($event),
            NodeGeneralizationVariantWasCreated::class => $this->whenNodeGeneralizationVariantWasCreated($event),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($event),
            NodeAggregateTypeWasChanged::class => $this->whenNodeAggregateTypeWasChanged($event),
            NodeAggregateNameWasChanged::class => $this->whenNodeAggregateNameWasChanged($event),
            ContentStreamWasRemoved::class => $this->whenContentStreamWasRemoved($event),
            // we don't need to handle all events,
            // DimensionSpacePointWasMoved is unhandled, because other workspaces MUST NOT contain changes i.e. nothing needs to be adjusted
            default => null,
        };
    }

    public function getState(): ChangeFinder
    {
        return $this->changeFinder;
    }

    private function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }

        $affectedDimensionSpacePoints = iterator_to_array($event->succeedingSiblingsForCoverage->toDimensionSpacePointSet());
        foreach ($affectedDimensionSpacePoints as $affectedDimensionSpacePoint) {
            // always the case due to constraint enforcement (at least one DSP is selected and must have a succeeding sibling or null)

            // We simply use the events DSPs here to store them as `Change` in even if the DSP is not necessarily  occupied.
            // this is not problematic as the DSP should only be used for providing additional information where a change has effects instead of locating its origin
            // todo possibly rename in the `Change` the field to '$affectedDimensionSpacePoint' field instead, as well use it now like that.

            $this->markAsMoved(
                $event->getContentStreamId(),
                $event->getNodeAggregateId(),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($affectedDimensionSpacePoint)
            );
        }
    }

    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        $this->markAsChanged(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->originDimensionSpacePoint
        );
    }

    private function whenNodeReferencesWereSet(NodeReferencesWereSet $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        foreach ($event->affectedSourceOriginDimensionSpacePoints as $dimensionSpacePoint) {
            $this->markAsChanged(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $dimensionSpacePoint
            );
        }
    }

    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        $this->markAsCreated(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->originDimensionSpacePoint
        );
    }

    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            if ($event->tag->equals(NeosSubtreeTag::removed())) {
                $this->modifyChange($event->contentStreamId, $event->nodeAggregateId, OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint), static function (Change $change) {
                    $change->deleted = true;
                });
                continue;
            }

            $this->markAsChanged(
                $event->contentStreamId,
                $event->nodeAggregateId,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
            );
        }
    }

    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            if ($event->tag->equals(NeosSubtreeTag::removed())) {
                $this->modifyChange($event->contentStreamId, $event->nodeAggregateId, OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint), static function (Change $change) {
                    $change->deleted = false;
                    $change->changed = true;
                });
                continue;
            }

            $this->markAsChanged(
                $event->contentStreamId,
                $event->nodeAggregateId,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
            );
        }
    }

    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }


        $this->dbal->executeStatement(
            'DELETE FROM ' . $this->tableNamePrefix . '
                WHERE
                    contentStreamId = :contentStreamId
                    AND nodeAggregateId = :nodeAggregateId
                    AND originDimensionSpacePointHash IN (:affectedDimensionSpacePointHashes)
                ',
            [
                'contentStreamId' => $event->contentStreamId->value,
                'nodeAggregateId' => $event->nodeAggregateId->value,
                'affectedDimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints
                    ->getPointHashes()
            ],
            [
                'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );

        foreach ($event->affectedCoveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $removalChange = new Change(
                $event->contentStreamId,
                $event->nodeAggregateId,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($coveredDimensionSpacePoint),
                created: false,
                changed: false,
                moved: false,
                deleted: true,
                removalAttachmentPoint: $event->removalAttachmentPoint
            );
            $removalChange->addToDatabase($this->dbal, $this->tableNamePrefix);
        }
    }

    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        $this->markAsCreated(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->specializationOrigin
        );
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        $this->markAsCreated(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->generalizationOrigin
        );
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        $this->markAsCreated(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->peerOrigin
        );
    }

    private function whenNodeAggregateTypeWasChanged(NodeAggregateTypeWasChanged $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        $this->markAggregateAsChanged(
            $event->contentStreamId,
            $event->nodeAggregateId,
        );
    }

    private function whenNodeAggregateNameWasChanged(NodeAggregateNameWasChanged $event): void
    {
        if ($event->workspaceName->isLive()) {
            return;
        }
        $this->markAggregateAsChanged(
            $event->contentStreamId,
            $event->nodeAggregateId,
        );
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        $this->removeChangesForContentStreamId($event->contentStreamId);
    }

    private function markAsChanged(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): void {
        $this->modifyChange(
            $contentStreamId,
            $nodeAggregateId,
            $originDimensionSpacePoint,
            static function (Change $change) {
                $change->changed = true;
            }
        );
    }

    private function markAggregateAsChanged(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
    ): void {
        $this->modifyChangeForAggregate(
            $contentStreamId,
            $nodeAggregateId,
            static function (Change $change) {
                $change->changed = true;
            }
        );
    }

    private function markAsCreated(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): void {
        $this->modifyChange(
            $contentStreamId,
            $nodeAggregateId,
            $originDimensionSpacePoint,
            static function (Change $change) {
                $change->created = true;
                $change->changed = true;
            }
        );
    }

    private function markAsMoved(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): void {
        $this->modifyChange(
            $contentStreamId,
            $nodeAggregateId,
            $originDimensionSpacePoint,
            static function (Change $change) {
                $change->moved = true;
            }
        );
    }

    private function modifyChange(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        callable $modifyFn
    ): void {
        $change = $this->getChange($contentStreamId, $nodeAggregateId, $originDimensionSpacePoint);

        if ($change === null) {
            $change = new Change($contentStreamId, $nodeAggregateId, $originDimensionSpacePoint, false, false, false, false);
            $modifyFn($change);
            $change->addToDatabase($this->dbal, $this->tableNamePrefix);
        } else {
            $modifyFn($change);
            $change->updateToDatabase($this->dbal, $this->tableNamePrefix);
        }
    }

    private function modifyChangeForAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        callable $modifyFn
    ): void {
        $change = $this->getChangeForAggregate($contentStreamId, $nodeAggregateId);

        if ($change === null) {
            $change = new Change($contentStreamId, $nodeAggregateId, null, false, false, false, false);
            $modifyFn($change);
            $change->addToDatabase($this->dbal, $this->tableNamePrefix);
        } else {
            $modifyFn($change);
            $change->updateToDatabase($this->dbal, $this->tableNamePrefix);
        }
    }

    private function getChange(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): ?Change {
        $changeRow = $this->dbal->executeQuery(
            'SELECT n.* FROM ' . $this->tableNamePrefix . ' n
WHERE n.contentStreamId = :contentStreamId
AND n.nodeAggregateId = :nodeAggregateId
AND n.originDimensionSpacePointHash = :originDimensionSpacePointHash',
            [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash
            ]
        )->fetch();

        // We always allow root nodes
        return $changeRow ? Change::fromDatabaseRow($changeRow) : null;
    }

    private function getChangeForAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
    ): ?Change {
        $changeRow = $this->dbal->executeQuery(
            'SELECT n.* FROM ' . $this->tableNamePrefix . ' n
WHERE n.contentStreamId = :contentStreamId
AND n.nodeAggregateId = :nodeAggregateId
AND n.origindimensionspacepointhash = :origindimensionspacepointhash',
            [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'origindimensionspacepointhash' => Change::AGGREGATE_DIMENSIONSPACEPOINT_HASH_PLACEHOLDER
            ]
        )->fetchAssociative();

        return $changeRow ? Change::fromDatabaseRow($changeRow) : null;
    }

    private function removeChangesForContentStreamId(ContentStreamId $contentStreamId): void
    {
        $statement = <<<SQL
            DELETE FROM {$this->tableNamePrefix}
            WHERE
                contentStreamId = :contentStreamId
        SQL;
        $this->dbal->executeStatement(
            $statement,
            [
                'contentStreamId' => $contentStreamId->value,
            ]
        );
    }
}
