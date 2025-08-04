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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\RestrictionHyperrelationRecord;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;

/**
 * The subtree tagging feature set for the hypergraph projector
 *
 * @internal
 */
trait SubtreeTagging
{
    /**
     * @throws \Throwable
     */
    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        $parameters = [
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'contentstreamid' => $event->contentStreamId->value,
            'affecteddimensionspacepointhashes' => $event->affectedDimensionSpacePoints->getPointHashes(),
            'subtreetag' => $event->tag->value
        ];

        $parameterTypes = [
            'affecteddimensionspacepointhashes' => Connection::PARAM_STR_ARRAY
        ];

        $query = <<<SQL
            update {$this->tableNames->subTreeRelation()} st
            set subtreetags = array(select distinct unnest(st.subtreetags || :subtreetag::varchar(36)))
            where st.contentstreamid = :contentstreamid
              and st.nodeaggregateid = :nodeaggregateid
              and st.dimensionspacepointhash in (:affecteddimensionspacepointhashes)
        SQL;
        $this->getDatabaseConnection()->executeQuery($query, $parameters, $parameterTypes);
    }

    // ARRAY(SELECT DISTINCT UNNEST(arr_str || '{a,b,c}'))
    /**
     * @throws \Throwable
     */
    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        $parameters = [
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'contentstreamid' => $event->contentStreamId->value,
            'affecteddimensionspacepointhashes' => $event->affectedDimensionSpacePoints->getPointHashes(),
            'subtreetag' => $event->tag->value
        ];

        $parameterTypes = [
            'affecteddimensionspacepointhashes' => Connection::PARAM_STR_ARRAY
        ];

        $query = <<<SQL
            update {$this->tableNames->subTreeRelation()} st
            set subtreetags = array_remove(st.subtreetags, :subtreetag::varchar(36))
            where st.contentstreamid = :contentstreamid
              and st.nodeaggregateid = :nodeaggregateid
              and st.dimensionspacepointhash in (:affecteddimensionspacepointhashes)
        SQL;
        $this->getDatabaseConnection()->executeQuery($query, $parameters, $parameterTypes);
    }

    abstract protected function getDatabaseConnection(): Connection;
}
