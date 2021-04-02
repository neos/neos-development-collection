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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateNameWasChanged;
use Neos\Flow\Annotations as Flow;

/**
 * The node disabling feature set for the hypergraph projector
 */
trait NodeRenaming
{
    use CopyOnWrite;

    /**
     * @throws \Throwable
     */
    public function whenNodeAggregateNameWasChanged(NodeAggregateNameWasChanged $event): void
    {
        $this->transactional(function () use($event) {
            foreach ($this->getProjectionHyperGraph()->findNodeRecordsForNodeAggregate(
                $event->getContentStreamIdentifier(),
                $event->getNodeAggregateIdentifier()
            ) as $originNode) {
                $this->copyOnWrite(
                    $event->getContentStreamIdentifier(),
                    $originNode,
                    function(NodeRecord $nodeRecord) use ($event) {
                        $nodeRecord->nodeName = $event->getNewNodeName();
                    }
                );
            }
        });
    }

    abstract protected function getProjectionHyperGraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(callable $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
