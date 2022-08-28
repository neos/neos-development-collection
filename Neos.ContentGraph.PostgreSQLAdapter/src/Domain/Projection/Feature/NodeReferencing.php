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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
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
        $this->transactional(function () use ($event) {
            foreach ($event->affectedSourceOriginDimensionSpacePoints as $originDimensionSpacePoint) {
                $nodeRecord = $this->getProjectionHypergraph()->findNodeRecordByOrigin(
                    $event->contentStreamIdentifier,
                    $originDimensionSpacePoint,
                    $event->sourceNodeAggregateIdentifier
                );

                if ($nodeRecord) {
                    $anchorPoint = $this->copyOnWrite(
                        $event->contentStreamIdentifier,
                        $nodeRecord,
                        function (NodeRecord $node) {
                        }
                    );

                    // remove old
                    $this->getDatabaseConnection()->delete($this->tableNamePrefix . '_referencerelation', [
                        'sourcenodeanchor' => $anchorPoint,
                        'name' => $event->referenceName
                    ]);

                    // set new
                    $position = 0;
                    foreach ($event->references as $reference) {
                        $referenceRecord = new ReferenceRelationRecord(
                            $anchorPoint,
                            $event->referenceName,
                            $position,
                            $reference->properties,
                            $reference->targetNodeAggregateIdentifier
                        );
                        $referenceRecord->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
                        $position++;
                    }
                } else {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
                }
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
