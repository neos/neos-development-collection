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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceRelationRecord;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;

/**
 * The node referencing feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeReferencing
{
    use CopyOnWrite;

    /**
     * @throws \Throwable
     */
    private function whenNodeReferencesWereSet(NodeReferencesWereSet $event): void
    {
        foreach ($event->affectedSourceOriginDimensionSpacePoints as $originDimensionSpacePoint) {
            $nodeRecord = $this->getReadQueries()->findNodeRecordByOrigin(
                $event->contentStreamId,
                $originDimensionSpacePoint,
                $event->nodeAggregateId
            );

            if ($nodeRecord) {
                $anchorPoint = $this->copyOnWrite(
                    $event->contentStreamId,
                    $nodeRecord,
                    function (NodeRecord $node) {
                    }
                );

                $position = 0;
                // FIXME can't we get rid of the loop and turn this into a single query?
                foreach ($event->references as $referencesForProperty) {
                    // TODO can't we turn this into two atomic queries?
                    $this->getDatabaseConnection()->delete($this->tableNames->referenceRelation(), [
                        'sourcenodeanchor' => $anchorPoint->value,
                        'name' => $referencesForProperty->referenceName->value
                    ]);

                    foreach ($referencesForProperty->references as $reference) {
                        // set new
                        $referenceRecord = new ReferenceRelationRecord(
                            $anchorPoint,
                            $referencesForProperty->referenceName,
                            $position,
                            $reference->properties,
                            $reference->targetNodeAggregateId
                        );
                        $this->getWriteQueries()->addReferenceToDatabase($this->getDatabaseConnection(), $referenceRecord);
                        $position++;
                    }
                }
            } else {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
        }
    }

    abstract protected function getReadQueries(): ProjectionReadQueries;
    abstract protected function getWriteQueries(): ProjectionWriteQueries;

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function getTableNames(): ContentGraphTableNames;
}
