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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceRelationRecord;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\EventStore\Model\EventEnvelope;

/**
 * The node referencing feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeReferencing
{
    /**
     * @throws \Throwable
     */
    private function whenNodeReferencesWereSet(NodeReferencesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        foreach ($event->affectedSourceOriginDimensionSpacePoints as $originDimensionSpacePoint) {
            // 1. Find the node by origin
            $sourceNode = $this->getReadQueries()->findNodeRecordByOrigin(
                $event->contentStreamId,
                $originDimensionSpacePoint,
                $event->nodeAggregateId
            );
            if ($sourceNode === null) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot apply NodeReferencesWereSet: node "%s" not found at origin %s in content stream %s',
                        $event->nodeAggregateId->value,
                        $originDimensionSpacePoint->toJson(),
                        $event->contentStreamId->value
                    ),
                    1716498900
                );
            }

            // 2. Copy-on-write if the node is shared across multiple content streams
            $numberOfContentStreams = $this->getReadQueries()
                ->countContentStreamCoverage($sourceNode->relationAnchorPoint);

            if ($numberOfContentStreams > 1) {
                // Copy the node record (no property changes for referencing)
                $newAnchor = $this->getWriteQueries()->insertNodeRecord(
                    $this->getDatabaseConnection(),
                    $sourceNode->nodeAggregateId,
                    $sourceNode->originDimensionSpacePoint,
                    $sourceNode->properties,
                    $sourceNode->nodeTypeName,
                    $sourceNode->classification,
                    $sourceNode->nodeName,
                    $sourceNode->timestamps->with(
                        lastModified: $eventEnvelope->recordedAt,
                        originalLastModified: self::initiatingDateTime($eventEnvelope),
                    ),
                );

                // Reassign ingoing hierarchy relations for this content stream
                foreach (
                    $this->getReadQueries()->findIngoingHierarchyHyperrelationRecords(
                        $event->contentStreamId,
                        $sourceNode->relationAnchorPoint
                    ) as $ingoingRelation
                ) {
                    $ingoingRelation->replaceChildNodeAnchor(
                        $sourceNode->relationAnchorPoint,
                        $newAnchor,
                        $this->getDatabaseConnection(),
                        $this->getTableNames()
                    );
                }

                // Reassign outgoing hierarchy relations for this content stream
                foreach (
                    $this->getReadQueries()->findOutgoingHierarchyHyperrelationRecords(
                        $event->contentStreamId,
                        $sourceNode->relationAnchorPoint
                    ) as $outgoingRelation
                ) {
                    $this->getWriteQueries()->replaceParentNodeAnchorOnHierarchyRecord(
                        $this->getDatabaseConnection(),
                        $outgoingRelation->getDatabaseIdentifier(),
                        $newAnchor
                    );
                }

                // Copy existing reference relations to the new anchor
                foreach (
                    $this->getReadQueries()->findOutgoingReferenceHyperrelationRecords(
                        $sourceNode->relationAnchorPoint
                    ) as $referenceRelation
                ) {
                    $copiedReference = $referenceRelation->withSourceNodeAnchor($newAnchor);
                    $this->getWriteQueries()->addReferenceToDatabase(
                        $this->getDatabaseConnection(),
                        $copiedReference
                    );
                }

                $activeAnchor = $newAnchor;
            } else {
                // Update timestamps on the existing node
                $sourceNode->timestamps = $sourceNode->timestamps->with(
                    lastModified: $eventEnvelope->recordedAt,
                    originalLastModified: self::initiatingDateTime($eventEnvelope),
                );
                $this->getWriteQueries()->updateNodeRecord(
                    $this->getDatabaseConnection(),
                    $sourceNode
                );
                $activeAnchor = $sourceNode->relationAnchorPoint;
            }

            // 3. Delete old references for the affected reference names
            $referenceNames = array_map(
                static fn (ReferenceName $name) => $name->value,
                $event->references->getReferenceNames()
            );
            if (!empty($referenceNames)) {
                $this->getDatabaseConnection()->executeStatement(
                    'DELETE FROM ' . $this->getTableNames()->referenceRelation()
                    . ' WHERE sourcenodeanchor = :sourceAnchor AND name IN (:names)',
                    [
                        'sourceAnchor' => $activeAnchor->value,
                        'names' => $referenceNames,
                    ],
                    [
                        'names' => ArrayParameterType::STRING,
                    ]
                );
            }

            // 4. Insert new reference records
            $position = 0;
            foreach ($event->references as $referencesByProperty) {
                foreach ($referencesByProperty->references as $reference) {
                    $this->getWriteQueries()->addReferenceToDatabase(
                        $this->getDatabaseConnection(),
                        new ReferenceRelationRecord(
                            $activeAnchor,
                            $referencesByProperty->referenceName,
                            $position,
                            $reference->properties->count() > 0
                                ? $reference->properties
                                : null,
                            $reference->targetNodeAggregateId
                        )
                    );
                    $position++;
                }
            }
        }
    }

    abstract protected function getDatabaseConnection(): Connection;
    abstract protected function getReadQueries(): ProjectionReadQueries;
    abstract protected function getWriteQueries(): ProjectionWriteQueries;
    abstract protected function getTableNames(): ContentGraphTableNames;
}
