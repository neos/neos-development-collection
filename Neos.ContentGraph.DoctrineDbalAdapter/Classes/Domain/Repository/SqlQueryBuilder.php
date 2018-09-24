<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Service\DbalClient;
use Neos\EventSourcedContentRepository\Domain;
use Neos\EventSourcedContentRepository\Domain\Model\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @api
 */
final class SqlQueryBuilder
{

    /**
     * @var string
     */
    protected $query = '';

    protected $parameters = [];
    protected $types = [];

    public function addToQuery($queryPart, string $markerToReplaceInQuery = null) {
        if ($markerToReplaceInQuery !== null) {
            $this->query = str_replace($markerToReplaceInQuery, $queryPart, $this->query);
        } else {
            $this->query .= ' ' . $queryPart;
        }
        return $this;
    }

    public function parameter($parameterName, $parameterValue, $parameterType = null) {
        $this->parameters[$parameterName] = $parameterValue;

        if ($parameterType !== null) {
            $this->types[$parameterName] = $parameterType;
        }

        return $this;
    }

    /**
     * @param Connection $connection
     * @return Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    public function execute(Connection $connection): Statement
    {
        return $connection->executeQuery($this->query, $this->parameters, $this->types);
    }
}
