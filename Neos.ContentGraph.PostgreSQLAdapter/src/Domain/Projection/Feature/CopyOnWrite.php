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
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;

/**
 * The copy on write feature set for the hypergraph projector
 */
trait CopyOnWrite
{
    /**
     * @throws \Throwable
     */
    public function copyOnWrite(
        ContentStreamIdentifier $originContentStreamIdentifier,
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
            $copiedNode->addToDatabase($this->getDatabaseConnection());

            $this->reassignIngoingHierarchyRelations(
                $originContentStreamIdentifier,
                $originNode->relationAnchorPoint,
                $copiedNodeRelationAnchorPoint
            );

            $this->reassignOutgoingHierarchyRelations(
                $originContentStreamIdentifier,
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
            $originNode->updateToDatabase($this->getDatabaseConnection());

            return $originNode->relationAnchorPoint;
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function reassignIngoingHierarchyRelations(
        ContentStreamIdentifier $originContentStreamIdentifier,
        NodeRelationAnchorPoint $originRelationAnchorPoint,
        NodeRelationAnchorPoint $targetRelationAnchorPoint
    ): void {
        foreach (
            $this->getProjectionHypergraph()->findIngoingHierarchyHyperrelationRecords(
                $originContentStreamIdentifier,
                $originRelationAnchorPoint
            ) as $ingoingHierarchyRelation
        ) {
            $ingoingHierarchyRelation->replaceChildNodeAnchor(
                $originRelationAnchorPoint,
                $targetRelationAnchorPoint,
                $this->getDatabaseConnection()
            );
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function reassignOutgoingHierarchyRelations(
        ContentStreamIdentifier $originContentStreamIdentifier,
        NodeRelationAnchorPoint $originRelationAnchorPoint,
        NodeRelationAnchorPoint $targetRelationAnchorPoint
    ): void {
        foreach (
            $this->getProjectionHypergraph()->findOutgoingHierarchyHyperrelationRecords(
                $originContentStreamIdentifier,
                $originRelationAnchorPoint
            ) as $outgoingHierarchyRelation
        ) {
            $outgoingHierarchyRelation->replaceParentNodeAnchor(
                $targetRelationAnchorPoint,
                $this->getDatabaseConnection()
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
            $copiedReferenceRelation->addToDatabase($this->getDatabaseConnection());
        }
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
