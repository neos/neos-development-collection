<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;

/**
 * The dimension space adjustment feature set for the hypergraph projector
 *
 * @internal
 */
trait DimensionSpaceAdjustment
{
    use CopyOnWrite;

    private function whenDimensionShineThroughWasAdded(DimensionShineThroughWasAdded $event): void
    {
        $parameters = [
            'contentStreamId' => $event->contentStreamId->value,
            'sourceDimensionSpacePointHash' => $event->source->hash,
            'targetDimensionSpacePointHash' => $event->target->hash,
            'targetDimensionSpacePoint' => $event->target->toJson(),
        ];

        // Register the new dimension space point
        $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
            'INSERT INTO ' . $this->getTableNames()->dimensionSpacePoints() . '
                (hash, dimensionspacepoint)
            VALUES (:targetDimensionSpacePointHash, :targetDimensionSpacePoint::jsonb)
            ON CONFLICT DO NOTHING',
            [
                'targetDimensionSpacePointHash' => $event->target->hash,
                'targetDimensionSpacePoint' => $event->target->toJson(),
            ]
        );

        // Copy all hierarchy relations from the source dimension space point to the target,
        // keeping the same parent/child anchors and subtree tags.
        $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
            'INSERT INTO ' . $this->getTableNames()->hierarchyRelation() . '
                (contentstreamid, parentnodeanchor,
                 dimensionspacepoint, dimensionspacepointhash, childnodeanchors, subtreetags)
            SELECT :contentStreamId, parentnodeanchor,
                :targetDimensionSpacePoint::json, :targetDimensionSpacePointHash, childnodeanchors, subtreetags
            FROM ' . $this->getTableNames()->hierarchyRelation() . ' source
            WHERE source.contentstreamid = :contentStreamId
              AND source.dimensionspacepointhash = :sourceDimensionSpacePointHash',
            $parameters
        );
    }

    private function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $parameters = [
            'contentStreamId' => $event->contentStreamId->value,
            'sourceDimensionSpacePointHash' => $event->source->hash,
            'targetDimensionSpacePointHash' => $event->target->hash,
            'targetDimensionSpacePoint' => $event->target->toJson(),
        ];

        // Register the new dimension space point
        $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
            'INSERT INTO ' . $this->getTableNames()->dimensionSpacePoints() . '
                (hash, dimensionspacepoint)
            VALUES (:targetDimensionSpacePointHash, :targetDimensionSpacePoint::jsonb)
            ON CONFLICT DO NOTHING',
            [
                'targetDimensionSpacePointHash' => $event->target->hash,
                'targetDimensionSpacePoint' => $event->target->toJson(),
            ]
        );

        // 1) Update origin dimension space point on nodes that originate in the source DSP
        //    using copy-on-write to avoid mutating nodes shared with other content streams
        $affectedNodeRecords = $this->getDatabaseConnection()->fetchAllAssociative(/** @lang PostgreSQL */
            'SELECT n.*
             FROM ' . $this->getTableNames()->node() . ' n
             INNER JOIN ' . $this->getTableNames()->hierarchyRelation() . ' h
                ON n.relationanchorpoint = ANY(h.childnodeanchors)
             WHERE h.contentstreamid = :contentStreamId
               AND h.dimensionspacepointhash = :sourceDimensionSpacePointHash
               AND n.origindimensionspacepointhash = :sourceDimensionSpacePointHash
               AND n.classification != \'root\'',
            [
                'contentStreamId' => $event->contentStreamId->value,
                'sourceDimensionSpacePointHash' => $event->source->hash,
            ]
        );

        foreach ($affectedNodeRecords as $row) {
            $nodeRecord = NodeRecord::fromDatabaseRow($row);
            $this->copyOnWrite(
                $event->contentStreamId,
                $nodeRecord,
                function (NodeRecord $node) use ($event) {
                    $node->originDimensionSpacePoint = OriginDimensionSpacePoint::fromDimensionSpacePoint($event->target);
                    $node->originDimensionSpacePointHash = $event->target->hash;
                }
            );
        }

        // 2) Update hierarchy relations to point to the new dimension space point
        $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
            'UPDATE ' . $this->getTableNames()->hierarchyRelation() . '
             SET dimensionspacepointhash = :targetDimensionSpacePointHash,
                 dimensionspacepoint = :targetDimensionSpacePoint::json
             WHERE contentstreamid = :contentStreamId
               AND dimensionspacepointhash = :sourceDimensionSpacePointHash',
            $parameters
        );
    }

    abstract protected function getDatabaseConnection(): Connection;
    abstract protected function getTableNames(): ContentGraphTableNames;
    abstract protected function getReadQueries(): ProjectionReadQueries;
    abstract protected function getWriteQueries(): ProjectionWriteQueries;
}
