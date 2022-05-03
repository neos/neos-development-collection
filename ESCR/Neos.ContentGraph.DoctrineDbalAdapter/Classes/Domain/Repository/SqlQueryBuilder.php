<?php
declare(strict_types=1);

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
use Doctrine\DBAL\ForwardCompatibility\DriverResultStatement;
use Doctrine\DBAL\ForwardCompatibility\DriverStatement;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @api
 */
final class SqlQueryBuilder
{
    protected string $query = '';

    /**
     * @var array<string,mixed>
     */
    protected array $parameters = [];

    /**
     * @var array<string,int|string>
     */
    protected array $types = [];

    public function addToQuery(string $queryPart, string $markerToReplaceInQuery = null): self
    {
        if ($markerToReplaceInQuery !== null) {
            $this->query = str_replace($markerToReplaceInQuery, $queryPart, $this->query);
        } else {
            $this->query .= ' ' . $queryPart;
        }

        return $this;
    }

    public function parameter(string $parameterName, mixed $parameterValue, string|int $parameterType = null): self
    {
        $this->parameters[$parameterName] = $parameterValue;

        if ($parameterType !== null) {
            $this->types[$parameterName] = $parameterType;
        }

        return $this;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @return DriverStatement<int,array<string,mixed>>|DriverResultStatement<int,array<string,mixed>>
     */
    public function execute(Connection $connection): DriverStatement|DriverResultStatement
    {
        return $connection->executeQuery($this->query, $this->parameters, $this->types);
    }
}
