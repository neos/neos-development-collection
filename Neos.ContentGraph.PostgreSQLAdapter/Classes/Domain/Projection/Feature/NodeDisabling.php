<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\RestrictionHyperrelationRecord;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasDisabled;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasEnabled;

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
                $event->getContentStreamIdentifier(),
                $event->getAffectedDimensionSpacePoints(),
                $event->getNodeAggregateIdentifier()
            );

            foreach ($descendantNodeAggregateIdentifiersByAffectedDimensionSpacePoint
                     as $dimensionSpacePointHash => $descendantNodeAggregateIdentifiers
            ) {
                $restrictionRelation = new RestrictionHyperrelationRecord(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePointHash,
                    $event->getNodeAggregateIdentifier(),
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
                $event->getContentStreamIdentifier(),
                $event->getAffectedDimensionSpacePoints(),
                $event->getNodeAggregateIdentifier(),
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
    abstract protected function transactional(callable $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
