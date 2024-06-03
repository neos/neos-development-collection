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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;

/**
 * The node disabling feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeRenaming
{
    use CopyOnWrite;

    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateNameWasChanged(NodeAggregateNameWasChanged $event): void
    {
        foreach (
            $this->getProjectionHyperGraph()->findNodeRecordsForNodeAggregate(
                $event->contentStreamId,
                $event->nodeAggregateId
            ) as $originNode
        ) {
            $this->copyOnWrite(
                $event->contentStreamId,
                $originNode,
                function (NodeRecord $nodeRecord) use ($event) {
                    $nodeRecord->nodeName = $event->newNodeName;
                }
            );
        }
    }

    abstract protected function getProjectionHyperGraph(): ProjectionHypergraph;

    abstract protected function getDatabaseConnection(): Connection;
}
