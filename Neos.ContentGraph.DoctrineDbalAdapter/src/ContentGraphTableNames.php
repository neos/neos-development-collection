<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

/**
 * Encapsulates table name generation for content graph tables
 * @internal
 */
final readonly class ContentGraphTableNames
{
    private function __construct(private string $tableNamePrefix)
    {
    }

    public static function withPrefix(string $tableNamePrefix): self
    {
        return new self($tableNamePrefix);
    }

    public function node(): string
    {
        return $this->tableNamePrefix . '_node';
    }

    public function hierachyRelation(): string
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
