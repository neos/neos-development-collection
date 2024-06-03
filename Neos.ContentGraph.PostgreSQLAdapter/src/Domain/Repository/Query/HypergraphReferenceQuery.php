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

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal
 */
final class HypergraphReferenceQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    public static function create(
        ContentStreamId $contentStreamId,
        string $nodeFieldsToFetch,
        string $tableNamePrefix
    ): self {
        $query = /** @lang PostgreSQL */'SELECT ' . $nodeFieldsToFetch
            . ', r.name as referencename, r.properties AS referenceproperties
     FROM ' . $tableNamePrefix . '_referencerelation r
        JOIN ' . $tableNamePrefix . '_node srcn
            ON srcn.relationanchorpoint = r.sourcenodeanchor
        JOIN ' . $tableNamePrefix . '_hierarchyhyperrelation srch
            ON srcn.relationanchorpoint = ANY(srch.childnodeanchors)
        JOIN ' . $tableNamePrefix . '_node tarn
            ON r.targetnodeaggregateid = tarn.nodeaggregateid
        JOIN ' . $tableNamePrefix . '_hierarchyhyperrelation tarh
            ON tarn.relationanchorpoint = ANY(tarh.childnodeanchors)
     WHERE srch.contentstreamid = :contentStreamId
     AND tarh.contentstreamid = :contentStreamId';
        $parameters = [
            'contentStreamId' => $contentStreamId->value,
        ];

        return new self(
            $query,
            $parameters,
            $tableNamePrefix
        );
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query;
        $query .= '
    AND srch.dimensionspacepointhash = :dimensionSpacePointHash
    AND tarh.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->hash;

        return new self($query, $parameters, $this->tableNamePrefix, $this->types);
    }

    public function withSourceNodeAggregateId(NodeAggregateId $sourceNodeAggregateId): self
    {
        $query = $this->query;
        $query .= '
    AND srcn.nodeaggregateid = :sourceNodeAggregateId';

        $parameters = $this->parameters;
        $parameters['sourceNodeAggregateId'] = $sourceNodeAggregateId->value;

        return new self($query, $parameters, $this->tableNamePrefix, $this->types);
    }

    public function withTargetNodeAggregateId(
        NodeAggregateId $targetNodeAggregateId
    ): self {
        $query = $this->query;
        $query .= '
    AND tarn.nodeaggregateid = :targetNodeAggregateId';

        $parameters = $this->parameters;
        $parameters['targetNodeAggregateId'] = $targetNodeAggregateId->value;

        return new self($query, $parameters, $this->tableNamePrefix, $this->types);
    }

    public function withReferenceName(ReferenceName $referenceName): self
    {
        $query = $this->query;
        $query .= '
    AND r.name = :referenceName';

        $parameters = $this->parameters;
        $parameters['referenceName'] = $referenceName->value;

        return new self($query, $parameters, $this->tableNamePrefix, $this->types);
    }

    public function withSourceRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause(
            $visibilityConstraints,
            $this->tableNamePrefix,
            'src'
        );

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }

    public function withTargetRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause(
            $visibilityConstraints,
            $this->tableNamePrefix,
            'tar'
        );

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }

    /**
     * @param array<string> $orderings
     */
    public function orderedBy(array $orderings): self
    {
        $query = $this->query;
        $query .= '
    ORDER BY ' . implode(', ', $orderings);

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }
}
