<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;

/**
 * @internal
 */
final readonly class StatementFactory
{
    private function __construct(
        private ContentGraphTableNames $tableNames,
    ) {
    }

    public static function for(ContentGraphTableNames $tableNames): self
    {
        return new self($tableNames);
    }

    public function forHierarchyRelation(ContentStreamLayers $contentStreamLayers): HierarchyRelationStatement
    {
        return HierarchyRelationStatement::for($this->tableNames)->withContentStreamLayers($contentStreamLayers);
    }
}
