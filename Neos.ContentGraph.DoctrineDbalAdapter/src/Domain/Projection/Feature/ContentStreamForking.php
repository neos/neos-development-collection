<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamDbId;

/**
 * @internal
 */
trait ContentStreamForking
{
    private function fastForwardHierarchyRelations(ContentStreamDbId $fastForwardContentStreamDbId, ContentStreamDbId $sourceContentStreamDbId): void
    {
        $removeStaleExclusiveEdges = <<<SQL
        DELETE
        FROM
          {$this->tableNames->hierarchyRelation()}
        WHERE
          (dimensionspacepointhash, parentnodeanchor, childnodeanchor, contentstreamdbid)
          IN (
            SELECT
              dimensionspacepointhash, parentnodeanchor, childnodeanchor, contentstreamdbid
            FROM
              {$this->tableNames->hierarchyRelation()} h_source
            WHERE
              h_source.contentstreamdbid = :fastForwardContentStreamDbId
              AND NOT EXISTS (
                SELECT
                  h_target.*
                FROM
                  {$this->tableNames->hierarchyRelation()} h_target
                WHERE
                  h_target.contentstreamdbid = :sourceContentStreamDbId
                  AND h_target.dimensionspacepointhash = h_source.dimensionspacepointhash
                  AND h_target.parentnodeanchor = h_source.parentnodeanchor
                  AND h_target.childnodeanchor = h_source.childnodeanchor
              )
          );
        SQL;

        $copyNewExclusiveEdges = <<<SQL
        INSERT INTO {$this->tableNames->hierarchyRelation()} (position, dimensionspacepointhash, parentnodeanchor, childnodeanchor, contentstreamdbid, subtreetags)
        SELECT
              position, dimensionspacepointhash, parentnodeanchor, childnodeanchor, :fastForwardContentStreamDbId AS contentstreamdbid, subtreetags
            FROM
              {$this->tableNames->hierarchyRelation()} h_target
            WHERE
              h_target.contentstreamdbid = :sourceContentStreamDbId
              AND NOT EXISTS (
                SELECT
                  h_source.*
                FROM
                  {$this->tableNames->hierarchyRelation()} h_source
                WHERE
                  h_source.contentstreamdbid = :fastForwardContentStreamDbId
                  AND h_source.dimensionspacepointhash = h_target.dimensionspacepointhash
                  AND h_source.parentnodeanchor = h_target.parentnodeanchor
                  AND h_source.childnodeanchor = h_target.childnodeanchor
              );
        SQL;

        try {
            $this->dbal->executeStatement($removeStaleExclusiveEdges, [
                'fastForwardContentStreamDbId' => $fastForwardContentStreamDbId->value,
                'sourceContentStreamDbId' => $sourceContentStreamDbId->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Todo: %s', $e->getMessage()), 1716489211, $e);
        }
        try {
            $this->dbal->executeStatement($copyNewExclusiveEdges, [
                'fastForwardContentStreamDbId' => $fastForwardContentStreamDbId->value,
                'sourceContentStreamDbId' => $sourceContentStreamDbId->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Todo: %s', $e->getMessage()), 1716489211, $e);
        }
    }
}
