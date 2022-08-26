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

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceRelationRecord;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

/**
 * @internal
 */
final class HypergraphReferenceQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    public static function create(
        ContentStreamIdentifier $contentStreamIdentifier,
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
            ON r.targetnodeaggregateidentifier = tarn.nodeaggregateidentifier
        JOIN ' . $tableNamePrefix . '_hierarchyhyperrelation tarh
            ON tarn.relationanchorpoint = ANY(tarh.childnodeanchors)
     WHERE srch.contentstreamidentifier = :contentStreamIdentifier
     AND tarh.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
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

    public function withSourceNodeAggregateIdentifier(NodeAggregateIdentifier $sourceNodeAggregateIdentifier): self
    {
        $query = $this->query;
        $query .= '
    AND srcn.nodeaggregateidentifier = :sourceNodeAggregateIdentifier';

        $parameters = $this->parameters;
        $parameters['sourceNodeAggregateIdentifier'] = (string)$sourceNodeAggregateIdentifier;

        return new self($query, $parameters, $this->tableNamePrefix, $this->types);
    }

    public function withTargetNodeAggregateIdentifier(
        NodeAggregateIdentifier $targetNodeAggregateIdentifier
    ): self {
        $query = $this->query;
        $query .= '
    AND tarn.nodeaggregateidentifier = :targetNodeAggregateIdentifier';

        $parameters = $this->parameters;
        $parameters['targetNodeAggregateIdentifier'] = (string)$targetNodeAggregateIdentifier;

        return new self($query, $parameters, $this->tableNamePrefix, $this->types);
    }

    public function withReferenceName(PropertyName $referenceName): self
    {
        $query = $this->query;
        $query .= '
    AND r.name = :referenceName';

        $parameters = $this->parameters;
        $parameters['referenceName'] = (string)$referenceName;

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
