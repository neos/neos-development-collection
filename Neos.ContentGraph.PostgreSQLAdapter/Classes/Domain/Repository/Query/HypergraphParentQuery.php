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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class HypergraphParentQuery implements HypergraphQueryInterface
{
    private string $query;

    private array $parameters;

    private function __construct($query, $parameters)
    {
        $this->query = $query;
        $this->parameters = $parameters;
    }

    public static function create(ContentStreamIdentifier $contentStreamIdentifier, array $fieldsToFetch = null): self
    {
        $query = /** @lang PostgreSQL */
            'SELECT pn.origindimensionspacepoint, pn.nodeaggregateidentifier, pn.nodetypename, pn.classification, pn.properties, pn.nodename,
                ph.contentstreamidentifier, ph.dimensionspacepoint
            FROM ' . HierarchyHyperrelationRecord::TABLE_NAME .' ph
            JOIN ' . NodeRecord::TABLE_NAME .' pn ON ph.childnodeanchors @> jsonb_build_array(pn.relationanchorpoint)::jsonb
            JOIN ' . HierarchyHyperrelationRecord::TABLE_NAME .' ch ON ch.parentnodeanchor = pn.relationanchorpoint
            JOIN ' . NodeRecord::TABLE_NAME .' cn ON ch.childnodeanchors @> jsonb_build_array(cn.relationanchorpoint)::jsonb
            WHERE ph.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        return new self($query, $parameters);
    }

    public function withNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        $query = $this->query .= '
            AND cn.nodeaggregateidentifier = :nodeAggregateIdentifier';

        $parameters = $this->parameters;
        $parameters['nodeAggregateIdentifier'] = (string)$nodeAggregateIdentifier;

        return new self($query, $parameters);
    }

    public function execute(Connection $databaseConnection): ResultStatement
    {
        return $databaseConnection->executeQuery($this->query, $this->parameters);
    }
}
