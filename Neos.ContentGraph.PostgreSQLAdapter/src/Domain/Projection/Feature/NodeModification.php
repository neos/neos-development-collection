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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;

/**
 * The node modification feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeModification
{
    use CopyOnWrite;

    /**
     * @throws \Throwable
     */
    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {
        $this->transactional(function () use ($event) {
            $nodeRecord = $this->getProjectionHypergraph()->findNodeRecordByOrigin(
                $event->contentStreamIdentifier,
                $event->originDimensionSpacePoint,
                $event->nodeAggregateIdentifier
            );
            if (is_null($nodeRecord)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $this->copyOnWrite(
                $event->contentStreamIdentifier,
                $nodeRecord,
                function (NodeRecord $node) use ($event) {
                    $node->properties = $node->properties->merge($event->propertyValues);
                }
            );
        });
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
