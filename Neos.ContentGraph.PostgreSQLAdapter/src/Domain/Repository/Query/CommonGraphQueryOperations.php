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
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;

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
    final protected function __construct(string $query, array $parameters, array $types = [])
    {
        $this->query = $query;
        $this->parameters = $parameters;
        $this->types = $types;
    }

    final public function withNodeTypeConstraints(NodeTypeConstraints $nodeTypeConstraints, string $prefix): self
    {
        $query = $this->query;
        $parameters = $this->parameters;
        $parameters['allowedNodeTypeNames'] = $nodeTypeConstraints->explicitlyAllowedNodeTypeNames;
        $parameters['disallowedNodeTypeNames'] = $nodeTypeConstraints->explicitlyDisallowedNodeTypeNames;
        $types = $this->types;
        $types['allowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
        $types['disallowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
        if (!$nodeTypeConstraints->explicitlyAllowedNodeTypeNames->isEmpty()) {
            if (!$nodeTypeConstraints->explicitlyDisallowedNodeTypeNames->isEmpty()) {
                if ($nodeTypeConstraints->isWildCardAllowed) {
                    $query .= '
            AND ' . $prefix . '.nodetypename NOT IN (:disallowedNodeTypeNames)
            OR ' . $prefix . '.nodetypename IN (:allowedNodeTypeNames)';
                } else {
                    $query .= '
            AND ' . $prefix . '.nodetypename IN (:allowedNodeTypeNames)
            AND ' . $prefix . '.nodetypename NOT IN (:disallowedNodeTypeNames)';
                }
            } else {
                if (!$nodeTypeConstraints->isWildCardAllowed) {
                    $query .= '
            AND ' . $prefix . '.nodetypename IN (:allowedNodeTypeNames)';
                }
            }
        } elseif (!$nodeTypeConstraints->explicitlyDisallowedNodeTypeNames->isEmpty()) {
            $query .= '
            AND ' . $prefix . '.nodetypename NOT IN (:disallowedNodeTypeNames)';
        }

        return new self($query, $parameters, $types);
    }

    public function withLimit(int $limit): self
    {
        $query = $this->query . '
            LIMIT ' . $limit;

        return new self($query, $this->parameters, $this->types);
    }

    public function withOffset(int $offset): self
    {
        $query = $this->query . '
            OFFSET ' . $offset;

        return new self($query, $this->parameters, $this->types);
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

    final public function execute(Connection $databaseConnection): ResultStatement
    {
        return $databaseConnection->executeQuery($this->query, $this->parameters, $this->types);
    }
}
