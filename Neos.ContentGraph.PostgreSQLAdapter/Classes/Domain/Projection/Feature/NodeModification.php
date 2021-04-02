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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePropertiesWereSet;
use Neos\Flow\Annotations as Flow;

/**
 * The node modification feature set for the hypergraph projector
 */
trait NodeModification
{
    use CopyOnWrite;

    /**
     * @throws \Throwable
     */
    public function whenNodePropertiesWereSet(NodePropertiesWereSet $event)
    {
        $this->transactional(function () use ($event) {
            $nodeRecord = $this->getProjectionHypergraph()->findNodeRecordByOrigin(
                $event->getContentStreamIdentifier(),
                $event->getOriginDimensionSpacePoint(),
                $event->getNodeAggregateIdentifier()
            );
            $this->copyOnWrite(
                $event->getContentStreamIdentifier(),
                $nodeRecord,
                function (NodeRecord $node) use ($event) {
                    $node->properties = $node->properties->merge($event->getPropertyValues());
                }
            );
        });
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(callable $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
