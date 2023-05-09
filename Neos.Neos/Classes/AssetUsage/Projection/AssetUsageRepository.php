<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Neos\AssetUsage\Dto\NodeAddress;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\Neos\AssetUsage\Dto\AssetIdsByProperty;
use Neos\Neos\AssetUsage\Dto\AssetUsage;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsages;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Neos\Neos\AssetUsage\Dto\AssetIdAndOriginalAssetId;

/**
 * @internal Not meant to be used in user land code. In order to look up asset usages the AssetUsageFinder can be used
 */
final class AssetUsageRepository
{
    public function __construct(
        private readonly Connection $dbal,
        private readonly string $tableNamePrefix,
    ) {
    }

    public function setup(): void
    {
        $schemaManager = $this->dbal->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }

        $schemaDiff = (new Comparator())->compare($schemaManager->createSchema(), self::databaseSchema($this->tableNamePrefix));
        foreach ($schemaDiff->toSaveSql($this->dbal->getDatabasePlatform()) as $statement) {
            $this->dbal->executeStatement($statement);
        }
    }

    private static function databaseSchema(string $tablePrefix): Schema
    {
        $schema = new Schema();

        $table = $schema->createTable($tablePrefix);
        $table->addColumn('assetid', Types::STRING)
            ->setLength(40)
            ->setNotnull(true)
            ->setDefault('');
        $table->addColumn('originalassetid', Types::STRING)
            ->setLength(40)
            ->setNotnull(false)
            ->setDefault(null);
        $table->addColumn('contentstreamid', Types::STRING)
            ->setLength(40)
            ->setNotnull(true)
            ->setDefault('');
        $table->addColumn('nodeaggregateid', Types::STRING)
            ->setLength(64)
            ->setNotnull(true)
            ->setDefault('');
        $table->addColumn('origindimensionspacepoint', Types::TEXT)
            ->setNotnull(false);
        $table->addColumn('origindimensionspacepointhash', Types::STRING)
            // this is an MD5(...), so 32 chars long
            ->setLength(32)
            ->setNotnull(true)
            ->setDefault('');
        $table->addColumn('propertyname', Types::STRING)
            ->setLength(255)
            ->setNotnull(true)
            ->setDefault('');

        $table
            ->addUniqueIndex(['assetid', 'originalassetid', 'contentstreamid', 'nodeaggregateid', 'origindimensionspacepointhash', 'propertyname'], 'assetperproperty')
            ->addIndex(['assetid'], 'assetid')
            ->addIndex(['originalassetid'], 'originalassetid')
            ->addIndex(['contentstreamid'], 'contentstreamid')
            ->addIndex(['nodeaggregateid'], 'nodeaggregateid')
            ->addIndex(['origindimensionspacepointhash'], 'origindimensionspacepointhash');

        return $schema;
    }

    public function findUsages(AssetUsageFilter $filter): AssetUsages
    {
        $queryBuilder = $this->dbal->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from($this->tableNamePrefix);
        if ($filter->hasAssetId()) {
            if ($filter->includeVariantsOfAsset === true) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq('assetId', ':assetId'),
                        $queryBuilder->expr()->eq('originalAssetId', ':assetId'),
                    )
                );
            } else {
                $queryBuilder->andWhere('assetId = :assetId');
            }

            $queryBuilder->setParameter('assetId', $filter->assetId);
        }
        if ($filter->hasContentStreamId()) {
            $queryBuilder->andWhere('contentStreamId = :contentStreamId');
            $queryBuilder->setParameter('contentStreamId', $filter->contentStreamId?->value);
        }
        if ($filter->groupByAsset) {
            $queryBuilder->addGroupBy('assetId');
        }
        if ($filter->groupByNode) {
            $queryBuilder->addGroupBy('nodeaggregateid');
            $queryBuilder->addGroupBy('origindimensionspacepointhash');
        }
        return new AssetUsages(function () use ($queryBuilder) {
            $result = $queryBuilder->execute();
            if (!$result instanceof Result) {
                throw new \RuntimeException(sprintf(
                    'Expected instance of "%s", got: "%s"',
                    Result::class,
                    get_debug_type($result)
                ), 1646320966);
            }
            /** @var array{assetid: string, contentstreamid: string, origindimensionspacepointhash: string, origindimensionspacepoint: string, nodeaggregateid: string, propertyname: string} $row */
            foreach ($result->iterateAssociative() as $row) {
                yield new AssetUsage(
                    $row['assetid'],
                    ContentStreamId::fromString($row['contentstreamid']),
                    OriginDimensionSpacePoint::fromJsonString($row['origindimensionspacepoint']),
                    NodeAggregateId::fromString($row['nodeaggregateid']),
                    $row['propertyname']
                );
            }
        }, function () use ($queryBuilder) {
            /** @var string $count */
            $count = $this->dbal->fetchOne(
                'SELECT COUNT(*) FROM (' . $queryBuilder->getSQL() . ') s',
                $queryBuilder->getParameters()
            );
            return (int)$count;
        });
    }

    public function addUsagesForNode(NodeAddress $nodeAddress, AssetIdsByProperty $assetIdsByProperty): void
    {
        // Delete all asset usage entries for newly set properties to ensure that removed or replaced assets are reflected
        $this->dbal->executeStatement('DELETE FROM ' . $this->tableNamePrefix
            . ' WHERE contentStreamId = :contentStreamId'
            . ' AND nodeAggregateId = :nodeAggregateId'
            . ' AND originDimensionSpacePointHash = :originDimensionSpacePointHash'
            . ' AND propertyName IN (:propertyNames)', [
            'contentStreamId' => $nodeAddress->contentStreamId->value,
            'nodeAggregateId' => $nodeAddress->nodeAggregateId->value,
            'originDimensionSpacePointHash' => $nodeAddress->dimensionSpacePoint->hash,
            'propertyNames' => $assetIdsByProperty->propertyNames(),
        ], [
            'propertyNames' => Connection::PARAM_STR_ARRAY,
        ]);

        foreach ($assetIdsByProperty as $propertyName => $assetIdAndOriginalAssetIds) {
            /** @var AssetIdAndOriginalAssetId $assetIdAndOriginalAssetId */
            foreach ($assetIdAndOriginalAssetIds as $assetIdAndOriginalAssetId) {
                try {
                    $this->dbal->insert($this->tableNamePrefix, [
                        'assetId' => $assetIdAndOriginalAssetId->assetId,
                        'originalAssetId' => $assetIdAndOriginalAssetId->originalAssetId,
                        'contentStreamId' => $nodeAddress->contentStreamId->value,
                        'nodeAggregateId' => $nodeAddress->nodeAggregateId->value,
                        'originDimensionSpacePoint' => $nodeAddress->dimensionSpacePoint->toJson(),
                        'originDimensionSpacePointHash' => $nodeAddress->dimensionSpacePoint->hash,
                        'propertyName' => $propertyName,
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    // A usage already exists for this node and property -> can be ignored
                }
            }
        }
    }

    public function removeContentStream(ContentStreamId $contentStreamId): void
    {
        $this->dbal->delete($this->tableNamePrefix, ['contentStreamId' => $contentStreamId->value]);
    }

    public function copyContentStream(
        ContentStreamId $sourceContentStreamId,
        ContentStreamId $targetContentStreamId,
    ): void {
        $this->dbal->executeStatement(
            'INSERT INTO ' . $this->tableNamePrefix . ' (assetid, originalassetid, contentstreamid, nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash, propertyname)'
            . ' SELECT assetid, originalassetid, :targetContentStreamId AS contentstreamid,'
            . ' nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash, propertyname'
            . ' FROM ' . $this->tableNamePrefix
            . ' WHERE contentStreamId = :sourceContentStreamId',
            [
                'sourceContentStreamId' => $sourceContentStreamId->value,
                'targetContentStreamId' => $targetContentStreamId->value,
            ]
        );
    }

    public function copyDimensions(
        OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        OriginDimensionSpacePoint $targetOriginDimensionSpacePoint,
    ): void {
        try {
            $this->dbal->executeStatement(
                'INSERT INTO ' . $this->tableNamePrefix . ' (assetid, originalassetid, contentstreamid, nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash, propertyname)'
                . ' SELECT assetid, originalassetid, contentstreamid, nodeaggregateid,'
                . ' :targetOriginDimensionSpacePoint AS origindimensionspacepoint,'
                . ' :targetOriginDimensionSpacePointHash AS origindimensionspacepointhash, propertyname'
                . ' FROM ' . $this->tableNamePrefix
                . ' WHERE originDimensionSpacePointHash = :sourceOriginDimensionSpacePointHash',
                [
                    'sourceOriginDimensionSpacePointHash' => $sourceOriginDimensionSpacePoint->hash,
                    'targetOriginDimensionSpacePoint' => $targetOriginDimensionSpacePoint->toJson(),
                    'targetOriginDimensionSpacePointHash' => $targetOriginDimensionSpacePoint->hash,
                ]
            );
        } catch (UniqueConstraintViolationException $e) {
            // A usage already exists for this node and property -> can be ignored
        }
    }

    public function remove(AssetUsage $usage): void
    {
        $this->dbal->delete($this->tableNamePrefix, [
            'assetId' => $usage->assetId,
            'contentStreamId' => $usage->contentStreamId->value,
            'nodeAggregateId' => $usage->nodeAggregateId->value,
            'originDimensionSpacePointHash' => $usage->originDimensionSpacePoint->hash,
            'propertyName' => $usage->propertyName,
        ]);
    }

    public function removeAsset(string $assetId): void
    {
        $this->dbal->delete($this->tableNamePrefix, [
            'assetId' => $assetId,
        ]);
    }

    public function removeNode(
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePoints,
    ): void {
        $this->dbal->executeStatement(
            'DELETE FROM ' . $this->tableNamePrefix
            . ' WHERE nodeAggregateId = :nodeAggregateId'
            . ' AND originDimensionSpacePointHash IN (:dimensionSpacePointHashes)',
            [
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes(),
            ],
            [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
            ]
        );
    }

    /**
     * @throws DbalException
     */
    public function reset(): void
    {
        /** @var AbstractPlatform|null $platform */
        $platform = $this->dbal->getDatabasePlatform();
        if ($platform === null) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to determine database platform for database "%s"',
                    $this->dbal->getDatabase()
                ),
                1645781464
            );
        }
        $this->dbal->executeStatement($platform->getTruncateTableSQL($this->tableNamePrefix));
    }

    public function getTableNamePrefix(): string
    {
        return $this->tableNamePrefix;
    }
}
