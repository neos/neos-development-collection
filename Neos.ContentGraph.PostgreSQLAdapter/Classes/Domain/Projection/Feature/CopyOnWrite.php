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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\Flow\Annotations as Flow;

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
    ) {
        $numberOfContentStreamsNodeDoesCover = $this->getProjectionHypergraph()
            ->countContentStreamCoverage($originNode->relationAnchorPoint);

        if ($numberOfContentStreamsNodeDoesCover > 1) {
            $copiedNodeRelationAnchorPoint = NodeRelationAnchorPoint::create();
            $copiedNode = clone $originNode;
            $copiedNode->relationAnchorPoint = $copiedNodeRelationAnchorPoint;
            $preprocessor($copiedNode);
            $copiedNode->addToDatabase($this->getDatabaseConnection());

            foreach ($this->getProjectionHypergraph()->findIngoingHierarchyHyperrelationRecords(
                $originContentStreamIdentifier,
                $originNode->relationAnchorPoint
            ) as $ingoingHierarchyRelation) {
                $ingoingHierarchyRelation->replaceChildNodeAnchor(
                    $originNode->relationAnchorPoint,
                    $copiedNode->relationAnchorPoint,
                    $this->getDatabaseConnection()
                );
            }

            foreach ($this->getProjectionHypergraph()->findOutgoingHierarchyHyperrelationRecords(
                $originContentStreamIdentifier,
                $originNode->relationAnchorPoint
            ) as $outgoingHierarchyRelation) {
                $outgoingHierarchyRelation->replaceParentNodeAnchor(
                    $copiedNode->relationAnchorPoint,
                    $this->getDatabaseConnection()
                );
            }

            foreach ($this->getProjectionHypergraph()->findOutgoingReferenceHyperrelationRecords(
                $originNode->relationAnchorPoint
            ) as $outgoingReferenceRelation) {
                $copiedReferenceRelation = clone $outgoingReferenceRelation;
                $copiedReferenceRelation->originNodeAnchor = $copiedNode->relationAnchorPoint;
                $copiedReferenceRelation->addToDatabase($this->getDatabaseConnection());
            }
        } else {
            // no reason to create a copy
            $preprocessor($originNode);
            $originNode->updateToDatabase($this->getDatabaseConnection());
        }
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(callable $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
