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
    private array $dimensionspacePointsRuntimeCache = [];

    private readonly ContentGraphTableNames $contentGraphTableNames;

    public function __construct(
        private readonly Connection $databaseConnection,
        string $tableNamePrefix
    ) {
        $this->contentGraphTableNames = ContentGraphTableNames::withPrefix($tableNamePrefix);
    }

    public function insertDimensionSpacePoint(AbstractDimensionSpacePoint $dimensionSpacePoint): void
    {
        if ($this->getCoordinatesByHashFromRuntimeCache($dimensionSpacePoint->hash) !== null) {
            return;
        }

        $this->dimensionspacePointsRuntimeCache[$dimensionSpacePoint->hash] = $dimensionSpacePoint->toJson();
        $this->writeDimensionSpacePoint($dimensionSpacePoint->hash, $dimensionSpacePoint->toJson());
    }

    /**
     * @param string $hash
     * @param array<string,string> $dimensionSpacePointCoordinates
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertDimensionSpacePointByHashAndCoordinates(string $hash, array $dimensionSpacePointCoordinates): void
    {
        if ($this->getCoordinatesByHashFromRuntimeCache($hash) !== null) {
            return;
        }

        $dimensionSpacePointCoordinatesJson = json_encode($dimensionSpacePointCoordinates, JSON_THROW_ON_ERROR);
        $this->dimensionspacePointsRuntimeCache[$hash] = $dimensionSpacePointCoordinatesJson;
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
        $this->databaseConnection->executeStatement(
            'INSERT IGNORE INTO ' . $this->contentGraphTableNames->dimensionSpacePoints() . ' (hash, dimensionspacepoint) VALUES (:dimensionspacepointhash, :dimensionspacepoint)',
            [
                'dimensionspacepointhash' => $hash,
                'dimensionspacepoint' => $dimensionSpacePointCoordinatesJson
            ]
        );
    }

    private function getCoordinatesByHashFromRuntimeCache(string $hash): ?string
    {
        return $this->dimensionspacePointsRuntimeCache[$hash] ?? null;
    }

    private function fillRuntimeCacheFromDatabase(): void
    {
        $allDimensionSpacePoints = $this->databaseConnection->fetchAllAssociative('SELECT hash, dimensionspacepoint FROM ' . $this->contentGraphTableNames->dimensionSpacePoints());
        foreach ($allDimensionSpacePoints as $dimensionSpacePointRow) {
            $this->dimensionspacePointsRuntimeCache[(string)$dimensionSpacePointRow['hash']] = (string)$dimensionSpacePointRow['dimensionspacepoint'];
        }
    }
}
