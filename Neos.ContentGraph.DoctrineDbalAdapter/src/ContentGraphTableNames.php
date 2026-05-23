<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Encapsulates table name generation for content graph tables
 * @internal
 */
final readonly class ContentGraphTableNames
{
    private function __construct(
        private string $tableNamePrefix
    ) {
    }

    public static function create(ContentRepositoryId $contentRepositoryId): self
    {
        return new self(sprintf('cr_%s_p_graph', $contentRepositoryId->value));
    }

    public function node(): string
    {
        return $this->tableNamePrefix . '_node';
    }

    /**
     * Warning, for reading any hierarchy information the {@see HierarchyRelationStatement} musts always be used to take layers into account.
     * Using the pure table in any queries must be done with exact care!
     */
    public function hierarchyRelation(): string
    {
        return $this->tableNamePrefix . '_hierarchyrelation';
    }

    public function hierarchyRelationForWorkspace(WorkspaceName $workspaceName): string
    {
        return $this->hierarchyRelation() . '_' . $workspaceName->value;
    }

    public function hierarchyRelationForWorkspaceInsertTrigger(WorkspaceName $workspaceName): string
    {
        return $this->hierarchyRelation() . '_inserter_' . $workspaceName->value;
    }

    public function hierarchyRelationForWorkspaceDeleteTrigger(WorkspaceName $workspaceName): string
    {
        return $this->hierarchyRelation() . '_deleter_' . $workspaceName->value;
    }

    public function dimensionSpacePoints(): string
    {
        return $this->tableNamePrefix . '_dimensionspacepoints';
    }

    public function referenceRelation(): string
    {
        return $this->tableNamePrefix . '_referencerelation';
    }

    public function workspace(): string
    {
        return $this->tableNamePrefix . '_workspace';
    }

    public function contentStream(): string
    {
        return $this->tableNamePrefix . '_contentstream';
    }

    public function contentStreamLayer(): string
    {
        return $this->tableNamePrefix . '_contentstreamlayer';
    }
}
