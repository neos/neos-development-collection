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
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;

trait CommonGraphQueryOperations
{
    private string $query;

    private array $parameters;

    private array $types;

    final protected function __construct($query, $parameters, $types = [])
    {
        $this->query = $query;
        $this->parameters = $parameters;
        $this->types = $types;
    }

    final public function withNodeTypeConstraints(NodeTypeConstraints $nodeTypeConstraints, string $prefix): self
    {
        $query = $this->query;
        $parameters = $this->parameters;
        $parameters['allowedNodeTypeNames'] = $nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames();
        $parameters['disallowedNodeTypeNames'] = $nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames();
        $types = $this->types;
        $types['allowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
        $types['disallowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
        if (!empty($nodeTypeConstraints->getExplicitlyAllowedNodeTypeNames())) {
            if (!empty($nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames())) {
                if ($nodeTypeConstraints->isWildcardAllowed()) {
                    $query .= '
            AND ' . $prefix . '.nodetypename NOT IN (:disallowedNodeTypeNames)
            OR ' . $prefix . '.nodetypename IN (:allowedNodeTypeNames)';
                } else {
                    $query .= '
            AND ' . $prefix . '.nodetypename IN (:allowedNodeTypeNames)
            AND ' . $prefix . '.nodetypename NOT IN (:disallowedNodeTypeNames)';
                }
            } else {
                if (!$nodeTypeConstraints->isWildcardAllowed()) {
                    $query .= '
            AND ' . $prefix . '.nodetypename IN (:allowedNodeTypeNames)';
                }
            }
        } elseif (!empty($nodeTypeConstraints->getExplicitlyDisallowedNodeTypeNames())) {
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

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    final public function execute(Connection $databaseConnection): ResultStatement
    {
        return $databaseConnection->executeQuery($this->query, $this->parameters, $this->types);
    }
}
