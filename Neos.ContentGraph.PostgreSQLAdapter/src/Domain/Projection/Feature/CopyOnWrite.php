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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The copy on write feature set for the hypergraph projector
 *
 * @internal
 */
trait CopyOnWrite
{
    /**
     * @throws \Throwable
     */
    public function copyOnWrite(
        ContentStreamId $originContentStreamId,
        NodeRecord $originNode,
        callable $preprocessor
    ): NodeRelationAnchorPoint {
        $numberOfContentStreamsNodeDoesCover = $this->getProjectionHypergraph()
            ->countContentStreamCoverage($originNode->relationAnchorPoint);

        if ($numberOfContentStreamsNodeDoesCover > 1) {
            $copiedNodeRelationAnchorPoint = NodeRelationAnchorPoint::create();
            $copiedNode = clone $originNode;
            $copiedNode->relationAnchorPoint = $copiedNodeRelationAnchorPoint;
            $preprocessor($copiedNode);
            $copiedNode->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

            $this->reassignIngoingHierarchyRelations(
                $originContentStreamId,
                $originNode->relationAnchorPoint,
                $copiedNodeRelationAnchorPoint
            );

            $this->reassignOutgoingHierarchyRelations(
                $originContentStreamId,
                $originNode->relationAnchorPoint,
                $copiedNodeRelationAnchorPoint
            );

            $this->copyOutgoingReferenceRelations(
                $originNode->relationAnchorPoint,
                $copiedNodeRelationAnchorPoint
            );

            return $copiedNodeRelationAnchorPoint;
        } else {
            // no reason to create a copy
            $preprocessor($originNode);
            $originNode->updateToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

            return $originNode->relationAnchorPoint;
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function reassignIngoingHierarchyRelations(
        ContentStreamId $originContentStreamId,
        NodeRelationAnchorPoint $originRelationAnchorPoint,
        NodeRelationAnchorPoint $targetRelationAnchorPoint
    ): void {
        foreach (
            $this->getProjectionHypergraph()->findIngoingHierarchyHyperrelationRecords(
                $originContentStreamId,
                $originRelationAnchorPoint
            ) as $ingoingHierarchyRelation
        ) {
            $ingoingHierarchyRelation->replaceChildNodeAnchor(
                $originRelationAnchorPoint,
                $targetRelationAnchorPoint,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
            );
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function reassignOutgoingHierarchyRelations(
        ContentStreamId $originContentStreamId,
        NodeRelationAnchorPoint $originRelationAnchorPoint,
        NodeRelationAnchorPoint $targetRelationAnchorPoint
    ): void {
        foreach (
            $this->getProjectionHypergraph()->findOutgoingHierarchyHyperrelationRecords(
                $originContentStreamId,
                $originRelationAnchorPoint
            ) as $outgoingHierarchyRelation
        ) {
            $outgoingHierarchyRelation->replaceParentNodeAnchor(
                $targetRelationAnchorPoint,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
            );
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function copyOutgoingReferenceRelations(
        NodeRelationAnchorPoint $sourceNodeAnchor,
        NodeRelationAnchorPoint $newSourceNodeAnchor
    ): void {
        foreach (
            $this->getProjectionHypergraph()->findOutgoingReferenceHyperrelationRecords(
                $sourceNodeAnchor
            ) as $outgoingReferenceRelation
        ) {
            $copiedReferenceRelation = $outgoingReferenceRelation->withSourceNodeAnchor($newSourceNodeAnchor);
            $copiedReferenceRelation->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
        }
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
