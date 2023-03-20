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
        $table->addColumn('assetidentifier', Types::STRING)
            ->setLength(40)
            ->setNotnull(true)
            ->setDefault('');
        $table->addColumn('contentstreamidentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true)
            ->setDefault('');
        $table->addColumn('nodeaggregateidentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true)
            ->setDefault('');
        $table->addColumn('origindimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true)
            ->setDefault('');
        $table->addColumn('propertyname', Types::STRING)
            ->setLength(255)
            ->setNotnull(true)
            ->setDefault('');

        $table
            ->addUniqueIndex(['assetidentifier', 'contentstreamidentifier', 'nodeaggregateidentifier', 'origindimensionspacepointhash', 'propertyname'], 'assetperproperty')
            ->addIndex(['assetidentifier'], 'assetidentifier')
            ->addIndex(['contentstreamidentifier'], 'contentstreamidentifier')
            ->addIndex(['nodeaggregateidentifier'], 'nodeaggregateidentifier')
            ->addIndex(['origindimensionspacepointhash'], 'origindimensionspacepointhash');

        return $schema;
    }

    public function findUsages(AssetUsageFilter $filter): AssetUsages
    {
        $queryBuilder = $this->dbal->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from($this->tableNamePrefix);
        if ($filter->hasAssetIdentifier()) {
            $queryBuilder->andWhere('assetIdentifier = :assetIdentifier');
            $queryBuilder->setParameter('assetIdentifier', $filter->assetIdentifier);
        }
        if ($filter->hasContentStreamIdentifier()) {
            $queryBuilder->andWhere('contentStreamIdentifier = :contentStreamIdentifier');
            $queryBuilder->setParameter('contentStreamIdentifier', $filter->contentStreamIdentifier);
        }
        if ($filter->groupByAsset) {
            $queryBuilder->addGroupBy('assetIdentifier');
        }
        if ($filter->groupByNode) {
            $queryBuilder->addGroupBy('nodeaggregateidentifier');
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
            /** @var array{assetidentifier: string, contentstreamidentifier: string, origindimensionspacepointhash: string, nodeaggregateidentifier: string, propertyname: string} $row */
            foreach ($result->iterateAssociative() as $row) {
                yield new AssetUsage(
                    $row['assetidentifier'],
                    ContentStreamId::fromString($row['contentstreamidentifier']),
                    $row['origindimensionspacepointhash'],
                    NodeAggregateId::fromString($row['nodeaggregateidentifier']),
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
        if ($assetIdsByProperty->hasPropertiesWithoutAssets()) {
            $this->dbal->executeStatement('DELETE FROM ' . $this->tableNamePrefix
                . ' WHERE contentStreamIdentifier = :contentStreamIdentifier'
                . ' AND nodeAggregateIdentifier = :nodeAggregateIdentifier'
                . ' AND originDimensionSpacePointHash = :originDimensionSpacePointHash'
                . ' AND propertyName IN (:propertyNames)', [
                'contentStreamIdentifier' => $nodeAddress->contentStreamId,
                'nodeAggregateIdentifier' => $nodeAddress->nodeAggregateId,
                'originDimensionSpacePointHash' => $nodeAddress->dimensionSpacePoint->hash,
                'propertyNames' => $assetIdsByProperty->propertyNamesWithoutAsset(),
            ], [
                'propertyNames' => Connection::PARAM_STR_ARRAY,
            ]);
        }
        foreach ($assetIdsByProperty as $propertyName => $assetIdentifiers) {
            foreach ($assetIdentifiers as $assetIdentifier) {
                try {
                    $this->dbal->insert($this->tableNamePrefix, [
                        'assetIdentifier' => $assetIdentifier,
                        'contentStreamIdentifier' => $nodeAddress->contentStreamId,
                        'nodeAggregateIdentifier' => $nodeAddress->nodeAggregateId,
                        'originDimensionSpacePointHash' => $nodeAddress->dimensionSpacePoint->hash,
                        'propertyName' => $propertyName,
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    // A usage already exists for this node and property -> can be ignored
                }
            }
        }
    }

    public function removeContentStream(ContentStreamId $contentStreamIdentifier): void
    {
        $this->dbal->delete($this->tableNamePrefix, ['contentStreamIdentifier' => $contentStreamIdentifier]);
    }

    public function copyContentStream(
        ContentStreamId $sourceContentStreamIdentifier,
        ContentStreamId $targetContentStreamIdentifier,
    ): void {
        $this->dbal->executeStatement(
            'INSERT INTO ' . $this->tableNamePrefix
            . ' SELECT assetidentifier, :targetContentStreamIdentifier AS contentstreamidentifier,'
            . ' nodeaggregateidentifier, origindimensionspacepointhash, propertyname'
            . ' FROM ' . $this->tableNamePrefix
            . ' WHERE contentStreamIdentifier = :sourceContentStreamIdentifier',
            [
                'sourceContentStreamIdentifier' => $sourceContentStreamIdentifier,
                'targetContentStreamIdentifier' => $targetContentStreamIdentifier,
            ]
        );
    }

    public function copyDimensions(
        OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        OriginDimensionSpacePoint $targetOriginDimensionSpacePoint,
    ): void {
        try {
            $this->dbal->executeStatement(
                'INSERT INTO ' . $this->tableNamePrefix
                . ' SELECT assetidentifier, contentstreamidentifier, nodeaggregateidentifier,'
                . ' :targetOriginDimensionSpacePointHash AS origindimensionspacepointhash, propertyname'
                . ' FROM ' . $this->tableNamePrefix
                . ' WHERE originDimensionSpacePointHash = :sourceOriginDimensionSpacePointHash',
                [
                    'sourceOriginDimensionSpacePointHash' => $sourceOriginDimensionSpacePoint->hash,
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
            'assetIdentifier' => $usage->assetIdentifier,
            'contentStreamIdentifier' => $usage->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $usage->nodeAggregateIdentifier,
            'originDimensionSpacePointHash' => $usage->originDimensionSpacePoint,
            'propertyName' => $usage->propertyName,
        ]);
    }

    public function removeAsset(string $assetIdentifier): void
    {
        $this->dbal->delete($this->tableNamePrefix, [
            'assetIdentifier' => $assetIdentifier,
        ]);
    }

    public function removeNode(
        NodeAggregateId $nodeAggregateIdentifier,
        DimensionSpacePointSet $dimensionSpacePoints,
    ): void {
        $this->dbal->executeStatement(
            'DELETE FROM ' . $this->tableNamePrefix
            . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier'
            . ' AND originDimensionSpacePointHash IN (:dimensionSpacePointHashes)',
            [
                'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
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
