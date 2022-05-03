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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeAggregateIdentifiers;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceHyperrelationRecord;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeReferencesWereSet;

/**
 * The node referencing feature set for the hypergraph projector
 */
trait NodeReferencing
{
    use CopyOnWrite;

    /**
     * @throws \Throwable
     */
    public function whenNodeReferencesWereSet(NodeReferencesWereSet $event): void
    {
        $this->transactional(function () use ($event) {
            $nodeRecord = $this->getProjectionHypergraph()->findNodeRecordByOrigin(
                $event->contentStreamIdentifier,
                $event->sourceOriginDimensionSpacePoint,
                $event->sourceNodeAggregateIdentifier
            );

            if ($nodeRecord) {
                $anchorPoint = $this->copyOnWrite(
                    $event->contentStreamIdentifier,
                    $nodeRecord,
                    function (NodeRecord $node) {
                    }
                );
                $existingReferenceRelation = $this->getProjectionHypergraph()->findReferenceRelationByOrigin(
                    $anchorPoint,
                    $event->referenceName
                );
                if ($existingReferenceRelation) {
                    $existingReferenceRelation->setDestinationNodeAggregateIdentifiers(
                        NodeAggregateIdentifiers::fromCollection($event->destinationNodeAggregateIdentifiers),
                        $this->getDatabaseConnection()
                    );
                } else {
                    $referenceRelation = new ReferenceHyperrelationRecord(
                        $anchorPoint,
                        $event->referenceName,
                        NodeAggregateIdentifiers::fromCollection($event->destinationNodeAggregateIdentifiers)
                    );
                    $referenceRelation->addToDatabase($this->getDatabaseConnection());
                }
            } else {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
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
