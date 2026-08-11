<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Query;

use Flowpack\QueryObjectBuilder\MySQL\Builder\IdentExp;

final readonly class HierarchyRelationColumnNames
{
    private function __construct(
        public IdentExp $id,
        public IdentExp $contentStreamLayer,
        public IdentExp $position,
        public IdentExp $dimensionSpacePointHash,
        public IdentExp $parentNodeAnchor,
        public IdentExp $childNodeAnchor,
        public IdentExp $subtreeTags,
    ) {
    }

    private static function create(string $prefix): self
    {
        return new self(
            IdentExp::n($prefix . 'id'),
            IdentExp::n($prefix . 'contentstreamlayer'),
            IdentExp::n($prefix . 'position'),
            IdentExp::n($prefix . 'dimensionspacepointhash'),
            IdentExp::n($prefix . 'parentnodeanchor'),
            IdentExp::n($prefix . 'childnodeanchor'),
            IdentExp::n($prefix . 'subtreetags'),
        );
    }

    public static function bare(): self
    {
        return self::create('');
    }

    public static function alias(string $alias): self
    {
        return self::create($alias . '.');
    }
}
