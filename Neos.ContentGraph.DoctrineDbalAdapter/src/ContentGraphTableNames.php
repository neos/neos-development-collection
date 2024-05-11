<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

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

    public function hierarchyRelation(): string
    {
        return $this->tableNamePrefix . '_hierarchyrelation';
    }

    public function dimensionSpacePoints(): string
    {
        return $this->tableNamePrefix . '_dimensionspacepoints';
    }

    public function referenceRelation(): string
    {
        return $this->tableNamePrefix . '_referencerelation';
    }

    public function checkpoint(): string
    {
        return $this->tableNamePrefix . '_checkpoint';
    }
}
