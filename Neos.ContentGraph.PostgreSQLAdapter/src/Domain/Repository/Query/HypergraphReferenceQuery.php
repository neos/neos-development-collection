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

final class HypergraphReferenceQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    public static function create(
        ContentStreamIdentifier $contentStreamIdentifier,
        string $nodeFieldsToFetch
    ): self {
        $query = /** @lang PostgreSQL */'SELECT ' . $nodeFieldsToFetch
            . ', r.name as referencename, r.properties AS referenceproperties
     FROM ' . ReferenceRelationRecord::TABLE_NAME . ' r
        JOIN neos_contentgraph_node orgn ON orgn.relationanchorpoint = r.originnodeanchor
        JOIN neos_contentgraph_hierarchyhyperrelation orgh ON orgn.relationanchorpoint = ANY(orgh.childnodeanchors)
        JOIN neos_contentgraph_node tarn ON r.targetnodeaggregateidentifier = tarn.nodeaggregateidentifier
        JOIN neos_contentgraph_hierarchyhyperrelation tarh ON tarn.relationanchorpoint = ANY(tarh.childnodeanchors)
     WHERE orgh.contentstreamidentifier = :contentStreamIdentifier
     AND tarh.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
        ];

        return new self(
            $query,
            $parameters
        );
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query;
        $query .= '
    AND orgh.dimensionspacepointhash = :dimensionSpacePointHash
    AND tarh.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->hash;

        return new self($query, $parameters, $this->types);
    }

    public function withOriginNodeAggregateIdentifier(NodeAggregateIdentifier $originNodeAggregateIdentifier): self
    {
        $query = $this->query;
        $query .= '
    AND orgn.nodeaggregateidentifier = :originNodeAggregateIdentifier';

        $parameters = $this->parameters;
        $parameters['originNodeAggregateIdentifier'] = (string)$originNodeAggregateIdentifier;

        return new self($query, $parameters, $this->types);
    }

    public function withTargetNodeAggregateIdentifier(
        NodeAggregateIdentifier $targetNodeAggregateIdentifier
    ): self {
        $query = $this->query;
        $query .= '
    AND tarn.nodeaggregateidentifier = :targetNodeAggregateIdentifier';

        $parameters = $this->parameters;
        $parameters['targetNodeAggregateIdentifier'] = (string)$targetNodeAggregateIdentifier;

        return new self($query, $parameters, $this->types);
    }

    public function withReferenceName(PropertyName $referenceName): self
    {
        $query = $this->query;
        $query .= '
    AND r.name = :referenceName';

        $parameters = $this->parameters;
        $parameters['referenceName'] = (string)$referenceName;

        return new self($query, $parameters, $this->types);
    }

    public function withOriginRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, 'org');

        return new self($query, $this->parameters, $this->types);
    }

    public function withTargetRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, 'tar');

        return new self($query, $this->parameters, $this->types);
    }

    /**
     * @param array<string> $orderings
     */
    public function orderedBy(array $orderings): self
    {
        $query = $this->query;
        $query .= '
    ORDER BY ' . implode(', ', $orderings);

        return new self($query, $this->parameters, $this->types);
    }
}
