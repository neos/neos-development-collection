<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Query;

use Flowpack\QueryObjectBuilder\MySQL\Builder\IdentExp;

final readonly class NodeColumnNames
{
    private function __construct(
        public IdentExp $star,
        public IdentExp $relationAnchorPoint,
        public IdentExp $nodeAggregateId,
        public IdentExp $originDimensionSpacePointHash,
        public IdentExp $nodeTypeName,
        public IdentExp $name,
        public IdentExp $properties,
        public IdentExp $classification,
        public IdentExp $created,
        public IdentExp $originalCreated,
        public IdentExp $lastModified,
        public IdentExp $originalLastModified,
    ) {
    }

    private static function create(string $prefix): self
    {
        return new self(
            IdentExp::n($prefix . '*'),
            IdentExp::n($prefix . 'relationanchorpoint'),
            IdentExp::n($prefix . 'nodeaggregateid'),
            IdentExp::n($prefix . 'origindimensionspacepointhash'),
            IdentExp::n($prefix . 'nodetypename'),
            IdentExp::n($prefix . 'name'),
            IdentExp::n($prefix . 'properties'),
            IdentExp::n($prefix . 'classification'),
            IdentExp::n($prefix . 'created'),
            IdentExp::n($prefix . 'originalcreated'),
            IdentExp::n($prefix . 'lastmodified'),
            IdentExp::n($prefix . 'originallastmodified'),
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
