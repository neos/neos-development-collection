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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\RestrictionHyperrelationRecord;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;

/**
 * The subtree tagging feature set for the hypergraph projector
 *
 * @internal
 */
trait SubtreeTagging
{
    /**
     * @throws \Throwable
     */
    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        $descendantNodeAggregateIdsByAffectedDimensionSpacePoint
            = $this->getProjectionHypergraph()->findDescendantNodeAggregateIds(
                $event->contentStreamId,
                $event->affectedDimensionSpacePoints,
                $event->nodeAggregateId
            );

        /** @codingStandardsIgnoreStart */
        foreach ($descendantNodeAggregateIdsByAffectedDimensionSpacePoint as $dimensionSpacePointHash => $descendantNodeAggregateIds) {
        /** @codingStandardsIgnoreEnd */
            $restrictionRelation = new RestrictionHyperrelationRecord(
                $event->contentStreamId,
                $dimensionSpacePointHash,
                $event->nodeAggregateId,
                $descendantNodeAggregateIds
            );

            $restrictionRelation->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
        }
    }

    /**
     * @throws \Throwable
     */
    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        $restrictionRelations = $this->getProjectionHypergraph()->findOutgoingRestrictionRelations(
            $event->contentStreamId,
            $event->affectedDimensionSpacePoints,
            $event->nodeAggregateId,
        );
        foreach ($restrictionRelations as $restrictionRelation) {
            $restrictionRelation->removeFromDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
        }
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    abstract protected function getDatabaseConnection(): Connection;
}
