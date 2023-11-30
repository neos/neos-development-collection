<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Neos\ContentRepository\Core\DimensionSpace\AbstractDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * A place to cache dimensionspacepoint data, it's deterministic so there should never be an issue with having this in memory.
 *
 * @internal you should never need this in userland code
 */
final class DimensionSpacePoints
{
    /**
     * @var string[]
     */
    private array $dimensionspacePoints = [];

    public function __construct(
        private readonly Connection $databaseConnection,
        private readonly string $tableNamePrefix
    ) {
    }

    public function insertDimensionSpacePoint(AbstractDimensionSpacePoint $dimensionSpacePoint): void
    {
        try {
            $this->databaseConnection->executeStatement(
                'INSERT INTO ' . $this->tableNamePrefix . '_dimensionspacepoints (hash, dimensionspacepoint) VALUES (:dimensionspacepointhash, :dimensionspacepoint)',
                [
                    'dimensionspacepointhash' => $dimensionSpacePoint->hash,
                    'dimensionspacepoint' => $dimensionSpacePoint->toJson()
                ]
            );
        } catch (UniqueConstraintViolationException $_) {
        }
    }

    /**
     * @param string $hash
     * @param array<string,string> $dimensionSpacePointCoordinates
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertDimensionSpacePointByHashAndCoordinates(string $hash, array $dimensionSpacePointCoordinates): void
    {
        try {
            $this->databaseConnection->executeStatement(
                'INSERT INTO ' . $this->tableNamePrefix . '_dimensionspacepoints (hash, dimensionspacepoint) VALUES (:dimensionspacepointhash, :dimensionspacepoint)',
                [
                    'dimensionspacepointhash' => $hash,
                    'dimensionspacepoint' => json_encode($dimensionSpacePointCoordinates, JSON_THROW_ON_ERROR)
                ]
            );
        } catch (UniqueConstraintViolationException $_) {
        }
    }

    public function getDimensionSpacePointByHash(string $hash): DimensionSpacePoint
    {
        if (!isset($this->dimensionspacePoints[$hash])) {
            $this->fillInternalIndex();
        }

        return DimensionSpacePoint::fromJsonString($this->dimensionspacePoints[$hash]);
    }

    public function getOriginDimensionSpacePointByHash(string $hash): OriginDimensionSpacePoint
    {
        if (!isset($this->dimensionspacePoints[$hash])) {
            $this->fillInternalIndex();
        }

        return OriginDimensionSpacePoint::fromJsonString($this->dimensionspacePoints[$hash]);
    }

    private function fillInternalIndex(): void
    {
        $allDimensionSpacePoints = $this->databaseConnection->fetchAllAssociativeIndexed('SELECT hash,dimensionspacepoint FROM ' . $this->tableNamePrefix . '_dimensionspacepoints');
        $this->dimensionspacePoints = array_map(static fn ($item) => $item['dimensionspacepoint'], $allDimensionSpacePoints);
    }
}
