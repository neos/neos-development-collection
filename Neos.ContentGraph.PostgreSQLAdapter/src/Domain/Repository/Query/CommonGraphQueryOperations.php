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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result as QueryResult;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria;

/**
 * @internal
 */
trait CommonGraphQueryOperations
{
    private string $query;

    /**
     * @var array<string,mixed>
     */
    private array $parameters;

    /**
     * @var array<string,int|string>
     */
    private array $types;

    /**
     * @param array<string,mixed> $parameters
     * @param array<string,int|string> $types
     */
    final protected function __construct(
        string $query,
        array $parameters,
        private readonly string $tableNamePrefix,
        array $types = []
    ) {
        $this->query = $query;
        $this->parameters = $parameters;
        $this->types = $types;
    }

    final public function withNodeTypeCriteria(
        ExpandedNodeTypeCriteria $nodeTypeCriteria,
        string $prefix
    ): self {
        $parameters = $this->parameters;
        $types = $this->types;
        $query = $this->query . QueryUtility::getNodeTypeCriteriaClause($nodeTypeCriteria, $prefix, $parameters, $types);
        return new self($query, $parameters, $this->tableNamePrefix, $types);
    }

    public function withLimit(int $limit): self
    {
        $query = $this->query . '
            LIMIT ' . $limit;

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }

    public function withOffset(int $offset): self
    {
        $query = $this->query . '
            OFFSET ' . $offset;

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return array<string,mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<string,int|string>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    final public function execute(Connection $databaseConnection): QueryResult
    {
        return $databaseConnection->executeQuery($this->query, $this->parameters, $this->types);
    }
}
