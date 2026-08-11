<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Flowpack\QueryObjectBuilder\MySQL\Builder\IdentExp;
use Flowpack\QueryObjectBuilder\MySQL\Q;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * Encapsulates table name generation for content graph tables
 * @internal
 */
final readonly class NewContentGraphTableNames
{
    private function __construct(
        private string $tableNamePrefix
    ) {
    }

    public static function create(ContentRepositoryId $contentRepositoryId): self
    {
        return new self(sprintf('cr_%s_p_graph', $contentRepositoryId->value));
    }

    public function node(): IdentExp
    {
        return Q::n($this->tableNamePrefix . '_node');
    }

    /**
     * Warning, for reading any hierarchy information the {@see HierarchyRelationSubquery} musts always be used to take layers into account.
     * Using the pure table in any queries must be done with exact care!
     */
    public function hierarchyRelation(): IdentExp
    {
        return Q::n($this->tableNamePrefix . '_hierarchyrelation');
    }

    public function dimensionSpacePoints(): IdentExp
    {
        return Q::n($this->tableNamePrefix . '_dimensionspacepoints');
    }

    public function referenceRelation(): IdentExp
    {
        return Q::n($this->tableNamePrefix . '_referencerelation');
    }

    public function workspace(): IdentExp
    {
        return Q::n($this->tableNamePrefix . '_workspace');
    }

    public function contentStream(): IdentExp
    {
        return Q::n($this->tableNamePrefix . '_contentstream');
    }

    public function contentStreamLayer(): IdentExp
    {
        return Q::n($this->tableNamePrefix . '_contentstreamlayer');
    }
}
