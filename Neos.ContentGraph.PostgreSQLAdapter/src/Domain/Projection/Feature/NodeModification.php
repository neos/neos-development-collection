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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\EventStore\Model\EventEnvelope;

/**
 * The node modification feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeModification
{
    /**
     * @throws \Throwable
     */
    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        // 1. Find the existing node by origin
        $sourceNode = $this->getReadQueries()->findNodeRecordByOrigin(
            $event->contentStreamId,
            $event->originDimensionSpacePoint,
            $event->nodeAggregateId
        );
        if ($sourceNode === null) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot apply NodePropertiesWereSet: node "%s" not found at origin %s in content stream %s',
                    $event->nodeAggregateId->value,
                    $event->originDimensionSpacePoint->toJson(),
                    $event->contentStreamId->value
                ),
                1716498800
            );
        }

        // 2. Compute the updated properties
        $updatedProperties = $sourceNode->properties
            ->merge($event->propertyValues)
            ->unsetProperties($event->propertiesToUnset);

        // 3. Check if copy-on-write is needed
        $numberOfContentStreams = $this->getReadQueries()
            ->countContentStreamCoverage($sourceNode->relationAnchorPoint);

        if ($numberOfContentStreams <= 1) {
            // No copy needed — update the node record directly
            $sourceNode->properties = $updatedProperties;
            $sourceNode->timestamps = $sourceNode->timestamps->with(
                lastModified: $eventEnvelope->recordedAt,
                originalLastModified: self::initiatingDateTime($eventEnvelope),
            );
            $this->getWriteQueries()->updateNodeRecord(
                $this->getDatabaseConnection(),
                $sourceNode
            );
        } else {
            // Copy on write: create a new node record with the updated properties
            $newAnchor = $this->getWriteQueries()->insertNodeRecord(
                $this->getDatabaseConnection(),
                $sourceNode->nodeAggregateId,
                $sourceNode->originDimensionSpacePoint,
                $updatedProperties,
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

            // Copy reference relations
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
        }
    }

    abstract protected function getDatabaseConnection(): Connection;
    abstract protected function getReadQueries(): ProjectionReadQueries;
    abstract protected function getWriteQueries(): ProjectionWriteQueries;
    abstract protected function getTableNames(): ContentGraphTableNames;
}
