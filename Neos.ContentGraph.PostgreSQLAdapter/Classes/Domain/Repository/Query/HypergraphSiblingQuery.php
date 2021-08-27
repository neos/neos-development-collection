<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class HypergraphSiblingQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    public static function create(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        HypergraphSiblingQueryMode $queryMode
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT * FROM neos_contentgraph_node sn,
    (
        SELECT n.relationanchorpoint, h.childnodeanchors, h.contentstreamidentifier, h.dimensionspacepointhash, h.dimensionspacepoint
            FROM neos_contentgraph_node n
            JOIN neos_contentgraph_hierarchyhyperrelation h ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND n.nodeaggregateidentifier = :nodeAggregateIdentifier
    ) AS sh
    WHERE sn.nodeaggregateidentifier != :nodeAggregateIdentifier
      ' . $queryMode->renderCondition();

    //AND sn.relationanchorpoint = ANY(sh.childnodeanchors[(array_position(sh.childnodeanchors, sh.relationanchorpoint)):])';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier
        ];

        return new self($query, $parameters);
    }

    public function withRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, 's');

        return new self($query, $this->parameters, $this->types);
    }
}
