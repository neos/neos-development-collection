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
    private function copyHierarchyRelations(ContentStreamDbId $newContentStreamDbId, ContentStreamDbId $sourceContentStreamDbId): void
    {
        //
        // 1) Copy HIERARCHY RELATIONS (this is the MAIN OPERATION here)
        //
        $insertRelationStatement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()} (
              parentnodeanchor,
              childnodeanchor,
              position,
              dimensionspacepointhash,
              subtreetags,
              contentstreamdbid
            )
            SELECT
              h.parentnodeanchor,
              h.childnodeanchor,
              h.position,
              h.dimensionspacepointhash,
              h.subtreetags,
              :newContentStreamDbId AS contentstreamdbid
            FROM
                {$this->tableNames->hierarchyRelation()} h
                WHERE h.contentstreamdbid = :sourceContentStreamDbId
        SQL;
        try {
            $this->dbal->executeStatement($insertRelationStatement, [
                'newContentStreamDbId' => $newContentStreamDbId->value,
                'sourceContentStreamDbId' => $sourceContentStreamDbId->value
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to insert hierarchy relation: %s', $e->getMessage()), 1716489211, $e);
        }

        // NOTE: as reference edges are attached to Relation Anchor Points (and they are lazily copy-on-written),
        // we do not need to copy reference edges here (but we need to do it during copy on write).
    }
}
