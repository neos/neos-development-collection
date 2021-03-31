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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeAggregateIdentifiers;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceHyperrelationRecord;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeReferencesWereSet;
use Neos\Flow\Annotations as Flow;

/**
 * The node referencing feature set for the hypergraph projector
 */
trait NodeReferencing
{
    /**
     * @throws \Throwable
     */
    public function whenNodeReferencesWereSet(NodeReferencesWereSet $event): void
    {
        $this->transactional(function() use($event) {
            $nodeRecord = $this->getProjectionHypergraph()->findNodeRecordByOrigin(
                $event->getContentStreamIdentifier(),
                $event->getSourceOriginDimensionSpacePoint(),
                $event->getSourceNodeAggregateIdentifier()
            );

            if ($nodeRecord) {
                $existingReferenceRelation = $this->getProjectionHypergraph()->findReferenceRelationByOrigin(
                    $nodeRecord->relationAnchorPoint,
                    $event->getReferenceName()
                );
                if ($existingReferenceRelation) {
                    $existingReferenceRelation->setDestinationNodeAggregateIdentifiers(
                        NodeAggregateIdentifiers::fromArray($event->getDestinationNodeAggregateIdentifiers()->getIterator()->getArrayCopy()),
                        $this->getDatabaseConnection()
                    );
                } else {
                    $referenceRelation = new ReferenceHyperrelationRecord(
                        $nodeRecord->relationAnchorPoint,
                        $event->getReferenceName(),
                        NodeAggregateIdentifiers::fromArray($event->getDestinationNodeAggregateIdentifiers()->getIterator()->getArrayCopy())
                    );
                    $referenceRelation->addToDatabase($this->getDatabaseConnection());
                }
            } else {
                // @todo log
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
