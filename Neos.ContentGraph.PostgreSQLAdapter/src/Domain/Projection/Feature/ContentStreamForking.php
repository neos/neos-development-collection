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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\RestrictionHyperrelationRecord;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;

/**
 * The content stream forking feature set for the hypergraph projector
 *
 * @internal
 */
trait ContentStreamForking
{
    /**
     * @throws \Throwable
     */
    private function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->transactional(function () use ($event) {
            $parameters = [
                'sourceContentStreamId' => (string)$event->sourceContentStreamId,
                'targetContentStreamId' => (string)$event->newContentStreamId
            ];

            $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
                'INSERT INTO ' . $this->tableNamePrefix . '_hierarchyhyperrelation
                    (contentstreamid, parentnodeanchor,
                     dimensionspacepoint, dimensionspacepointhash, childnodeanchors)
                SELECT :targetContentStreamId, parentnodeanchor,
                    dimensionspacepoint, dimensionspacepointhash, childnodeanchors
                FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation source
                WHERE source.contentstreamid = :sourceContentStreamId',
                $parameters
            );

            $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
                'INSERT INTO ' . $this->tableNamePrefix . '_restrictionhyperrelation
                    (contentstreamid, dimensionspacepointhash,
                     originnodeaggregateid, affectednodeaggregateids)
                SELECT :targetContentStreamId, dimensionspacepointhash,
                    originnodeaggregateid, affectednodeaggregateids
                FROM ' . $this->tableNamePrefix . '_restrictionhyperrelation source
                WHERE source.contentstreamid = :sourceContentStreamId',
                $parameters
            );
        });
    }

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
