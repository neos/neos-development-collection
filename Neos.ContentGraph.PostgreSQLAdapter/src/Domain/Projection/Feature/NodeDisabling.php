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
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;

/**
 * The node disabling feature set for the hypergraph projector
 */
trait NodeDisabling
{
    /**
     * @throws \Throwable
     */
    public function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        $this->transactional(function () use ($event) {
            $descendantNodeAggregateIdentifiersByAffectedDimensionSpacePoint
                = $this->getProjectionHypergraph()->findDescendantNodeAggregateIdentifiers(
                    $event->contentStreamIdentifier,
                    $event->affectedDimensionSpacePoints,
                    $event->nodeAggregateIdentifier
                );

            /** @codingStandardsIgnoreStart */
            foreach ($descendantNodeAggregateIdentifiersByAffectedDimensionSpacePoint as $dimensionSpacePointHash => $descendantNodeAggregateIdentifiers) {
            /** @codingStandardsIgnoreEnd */
                $restrictionRelation = new RestrictionHyperrelationRecord(
                    $event->contentStreamIdentifier,
                    $dimensionSpacePointHash,
                    $event->nodeAggregateIdentifier,
                    $descendantNodeAggregateIdentifiers
                );

                $restrictionRelation->addToDatabase($this->getDatabaseConnection());
            }
        });
    }

    /**
     * @throws \Throwable
     */
    public function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        $this->transactional(function () use ($event) {
            $restrictionRelations = $this->getProjectionHypergraph()->findOutgoingRestrictionRelations(
                $event->contentStreamIdentifier,
                $event->affectedDimensionSpacePoints,
                $event->nodeAggregateIdentifier,
            );
            foreach ($restrictionRelations as $restrictionRelation) {
                $restrictionRelation->removeFromDatabase($this->getDatabaseConnection());
            }
        });
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
