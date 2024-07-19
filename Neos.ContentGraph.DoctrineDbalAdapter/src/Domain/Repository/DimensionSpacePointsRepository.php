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
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\DimensionSpace\AbstractDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * A place to cache dimensionspacepoint data, it's deterministic so there should never be an issue with having this in memory.
 *
 * @internal you should never need this in userland code
 */
final class DimensionSpacePointsRepository
{
    /**
     * @var array<string, string>
     */
    private array $dimensionSpacePointsRuntimeCache = [];

    public function __construct(
        private readonly Connection $dbal,
        private readonly ContentGraphTableNames $tableNames,
    ) {
    }

    public function insertDimensionSpacePoint(AbstractDimensionSpacePoint $dimensionSpacePoint): void
    {
        if ($this->getCoordinatesByHashFromRuntimeCache($dimensionSpacePoint->hash) !== null) {
            return;
        }

        $this->dimensionSpacePointsRuntimeCache[$dimensionSpacePoint->hash] = $dimensionSpacePoint->toJson();
        $this->writeDimensionSpacePoint($dimensionSpacePoint->hash, $dimensionSpacePoint->toJson());
    }

    /**
     * @param array<string,string> $dimensionSpacePointCoordinates
     */
    public function insertDimensionSpacePointByHashAndCoordinates(string $hash, array $dimensionSpacePointCoordinates): void
    {
        if ($this->getCoordinatesByHashFromRuntimeCache($hash) !== null) {
            return;
        }

        try {
            $dimensionSpacePointCoordinatesJson = json_encode($dimensionSpacePointCoordinates, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-encode dimension space point coordinates: %s', $e->getMessage()), 1716474016, $e);
        }
        $this->dimensionSpacePointsRuntimeCache[$hash] = $dimensionSpacePointCoordinatesJson;
        $this->writeDimensionSpacePoint($hash, $dimensionSpacePointCoordinatesJson);
    }

    public function getOriginDimensionSpacePointByHash(string $hash): OriginDimensionSpacePoint
    {
        $coordinates = $this->getCoordinatesByHashFromRuntimeCache($hash);
        if ($coordinates === null) {
            $this->fillRuntimeCacheFromDatabase();
            $coordinates = $this->getCoordinatesByHashFromRuntimeCache($hash);
        }

        if ($coordinates === null) {
            throw new \RuntimeException(sprintf('A DimensionSpacePoint record with the given hash "%s" was not found in the projection, cannot determine coordinates.', $hash), 1710335509);
        }

        return OriginDimensionSpacePoint::fromJsonString($coordinates);
    }

    private function writeDimensionSpacePoint(string $hash, string $dimensionSpacePointCoordinatesJson): void
    {
        try {
            $this->dbal->executeStatement(
                'INSERT IGNORE INTO ' . $this->tableNames->dimensionSpacePoints() . ' (hash, dimensionspacepoint) VALUES (:dimensionspacepointhash, :dimensionspacepoint)',
                [
                    'dimensionspacepointhash' => $hash,
                    'dimensionspacepoint' => $dimensionSpacePointCoordinatesJson
                ]
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to insert dimension space point to database: %s', $e->getMessage()), 1716474073, $e);
        }
    }

    private function getCoordinatesByHashFromRuntimeCache(string $hash): ?string
    {
        return $this->dimensionSpacePointsRuntimeCache[$hash] ?? null;
    }

    private function fillRuntimeCacheFromDatabase(): void
    {
        $allDimensionSpacePointsStatement = <<<SQL
            SELECT hash, dimensionspacepoint FROM {$this->tableNames->dimensionSpacePoints()}
        SQL;
        try {
            $allDimensionSpacePoints = $this->dbal->fetchAllAssociative($allDimensionSpacePointsStatement);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load dimension space points from database: %s', $e->getMessage()), 1716488678, $e);
        }
        foreach ($allDimensionSpacePoints as $dimensionSpacePointRow) {
            $this->dimensionSpacePointsRuntimeCache[(string)$dimensionSpacePointRow['hash']] = (string)$dimensionSpacePointRow['dimensionspacepoint'];
        }
    }
}
