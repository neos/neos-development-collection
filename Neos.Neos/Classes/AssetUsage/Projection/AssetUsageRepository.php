<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\AssetUsage\Dto\AssetIdAndOriginalAssetId;
use Neos\Neos\AssetUsage\Dto\AssetIdsByProperty;
use Neos\Neos\AssetUsage\Dto\AssetUsage;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsageNodeAddress;
use Neos\Neos\AssetUsage\Dto\AssetUsages;

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

    public function setUp(): void
    {
        foreach (DbalSchemaDiff::determineRequiredSqlStatements($this->dbal, $this->databaseSchema()) as $statement) {
            $this->dbal->executeStatement($statement);
        }
    }

    /**
     * @return false|non-empty-string false if everything is okay, otherwise the details string, why a setup is required
     */
    public function isSetupRequired(): false|string
    {
        $requiredSqlStatements = DbalSchemaDiff::determineRequiredSqlStatements($this->dbal, $this->databaseSchema());
        if ($requiredSqlStatements !== []) {
            return sprintf('The following SQL statement%s required: %s', count($requiredSqlStatements) !== 1 ? 's are' : ' is', implode(chr(10), $requiredSqlStatements));
        }
        return false;
    }

    private function databaseSchema(): Schema
    {
        $schemaManager = $this->dbal->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }
        $assetUsageTable = new Table($this->tableNamePrefix, [
            (new Column('assetid', Type::getType(Types::STRING)))->setLength(40)->setNotnull(true)->setDefault(''),
            (new Column('originalassetid', Type::getType(Types::STRING)))->setLength(40)->setNotnull(false)->setDefault(null),
            DbalSchemaFactory::columnForWorkspaceName('workspacename')->setNotNull(true),
            DbalSchemaFactory::columnForNodeAggregateId('nodeaggregateid')->setNotNull(true),
            DbalSchemaFactory::columnForDimensionSpacePoint('origindimensionspacepoint')->setNotNull(false),
            DbalSchemaFactory::columnForDimensionSpacePointHash('origindimensionspacepointhash')->setNotNull(true),
            (new Column('propertyname', Type::getType(Types::STRING)))->setLength(255)->setNotnull(true)->setDefault('')
        ]);

        $assetUsageTable
            ->addUniqueIndex(['assetid', 'originalassetid', 'workspacename', 'nodeaggregateid', 'origindimensionspacepointhash', 'propertyname'], 'assetperproperty')
            ->addIndex(['assetid'])
            ->addIndex(['originalassetid'])
            ->addIndex(['workspacename'])
            ->addIndex(['nodeaggregateid'])
            ->addIndex(['origindimensionspacepointhash']);


        $workspaceChainTable = new Table($this->getWorkspacesTableName(), [
            DbalSchemaFactory::columnForWorkspaceName('workspacename')->setNotNull(true),
            DbalSchemaFactory::columnForWorkspaceName('baseworkspacename')->setNotNull(false),
        ]);

        $workspaceChainTable
            ->addUniqueIndex(['workspacename', 'baseworkspacename'], 'workspacerelation')
            ->addIndex(['workspacename'])
            ->addIndex(['baseworkspacename']);

        return DbalSchemaFactory::createSchemaWithTables($schemaManager, [$assetUsageTable, $workspaceChainTable]);
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
        if ($filter->hasWorkspaceName()) {
            $queryBuilder->andWhere('workspacename = :workspaceName');
            $queryBuilder->setParameter('workspaceName', $filter->workspaceName?->value);
        }
        if ($filter->groupByAsset) {
            $queryBuilder->addGroupBy('assetid');
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
            /** @var array{assetid: string, workspacename: string, origindimensionspacepointhash: string, origindimensionspacepoint: string, nodeaggregateid: string, propertyname: string} $row */
            foreach ($result->iterateAssociative() as $row) {
                yield new AssetUsage(
                    $row['assetid'],
                    WorkspaceName::fromString($row['workspacename']),
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

    public function addUsagesForNode(AssetUsageNodeAddress $nodeAddress, AssetIdsByProperty $assetIdsByProperty): void
    {
        // Delete all asset usage entries for newly set properties to ensure that removed or replaced assets are reflected
        $this->deleteAssetUsageByNodeAddressAndPropertyNames($nodeAddress, PropertyNames::fromArray($assetIdsByProperty->propertyNames()));

        foreach ($assetIdsByProperty as $propertyName => $assetIdAndOriginalAssetIds) {
            /** @var AssetIdAndOriginalAssetId $assetIdAndOriginalAssetId */
            foreach ($assetIdAndOriginalAssetIds as $assetIdAndOriginalAssetId) {
                try {
                    $this->dbal->insert($this->tableNamePrefix, [
                        'assetid' => $assetIdAndOriginalAssetId->assetId,
                        'originalassetid' => $assetIdAndOriginalAssetId->originalAssetId,
                        'workspacename' => $nodeAddress->workspaceName->value,
                        'nodeaggregateid' => $nodeAddress->nodeAggregateId->value,
                        'origindimensionspacepoint' => $nodeAddress->dimensionSpacePoint->toJson(),
                        'origindimensionspacepointhash' => $nodeAddress->dimensionSpacePoint->hash,
                        'propertyname' => $propertyName,
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    // A usage already exists for this node and property -> can be ignored
                }
            }
        }
    }

    public function deleteAssetUsageByNodeAddressAndPropertyNames(AssetUsageNodeAddress $nodeAddress, PropertyNames $propertyNames): void
    {
        $this->dbal->executeStatement('DELETE FROM ' . $this->tableNamePrefix
            . ' WHERE workspacename = :workspaceName'
            . ' AND nodeaggregateid = :nodeAggregateId'
            . ' AND origindimensionspacepointhash = :originDimensionSpacePointHash'
            . ' AND propertyname IN (:propertyNames)', [
            'workspaceName' => $nodeAddress->workspaceName->value,
            'nodeAggregateId' => $nodeAddress->nodeAggregateId->value,
            'originDimensionSpacePointHash' => $nodeAddress->dimensionSpacePoint->hash,
            'propertyNames' => array_map(static fn(PropertyName $propertyName) => $propertyName->value, iterator_to_array($propertyNames)),
        ], [
            'propertyNames' => Connection::PARAM_STR_ARRAY,
        ]);
    }

    public function removeByWorkspaceName(WorkspaceName $workspaceName): void
    {
        $this->dbal->delete($this->tableNamePrefix, ['workspacename' => $workspaceName->value]);
    }

    public function copyNodeAggregateFromBaseWorkspace(
        NodeAggregateId $nodeAggregateId,
        WorkspaceName $workspaceName,
        OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        OriginDimensionSpacePoint $targetOriginDimensionSpacePoint,
    ): void {
        try {
            $workspaceChain = [$workspaceName, ...$this->getBaseWorkspaces($workspaceName)];
            foreach ($workspaceChain as $baseWorkspace) {
                $affectedRows = $this->dbal->executeStatement(
                    'INSERT INTO ' . $this->tableNamePrefix . ' (assetid, originalassetid, workspacename, nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash, propertyname)'
                    . ' SELECT assetid, originalassetid, :workspaceName, nodeaggregateid,'
                    . ' :targetOriginDimensionSpacePoint AS origindimensionspacepoint,'
                    . ' :targetOriginDimensionSpacePointHash AS origindimensionspacepointhash, propertyname'
                    . ' FROM ' . $this->tableNamePrefix
                    . ' WHERE originDimensionSpacePointHash = :sourceOriginDimensionSpacePointHash'
                    . '  AND nodeaggregateid = :nodeAggregateId'
                    . '  AND workspacename = :baseWorkspaceName',
                    [
                        'nodeAggregateId' => $nodeAggregateId->value,
                        'workspaceName' => $workspaceName->value,
                        'baseWorkspaceName' => $baseWorkspace->value,
                        'sourceOriginDimensionSpacePointHash' => $sourceOriginDimensionSpacePoint->hash,
                        'targetOriginDimensionSpacePoint' => $targetOriginDimensionSpacePoint->toJson(),
                        'targetOriginDimensionSpacePointHash' => $targetOriginDimensionSpacePoint->hash,
                    ]
                );

                if ($affectedRows > 0) {
                    // We found a baseWorkspace with an assetUsage
                    return;
                }
            }
        } catch (UniqueConstraintViolationException $e) {
            // A usage already exists for this node and property -> can be ignored
        }
    }

    public function remove(AssetUsage $usage): void
    {
        $this->dbal->delete($this->tableNamePrefix, [
            'assetid' => $usage->assetId,
            'workspacename' => $usage->workspaceName->value,
            'nodeaggregateid' => $usage->nodeAggregateId->value,
            'origindimensionspacepointhash' => $usage->originDimensionSpacePoint->hash,
            'propertyname' => $usage->propertyName,
        ]);
    }

    public function removeAsset(string $assetId): void
    {
        $this->dbal->delete($this->tableNamePrefix, [
            'assetId' => $assetId,
        ]);
    }

    public function removeNodeInWorkspace(
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePoints,
        WorkspaceName $workspaceName
    ): void {
        $this->dbal->executeStatement(
            'DELETE FROM ' . $this->tableNamePrefix
            . ' WHERE nodeaggregateid = :nodeAggregateId'
            . ' AND origindimensionspacepointhash IN (:dimensionSpacePointHashes)'
            . ' AND workspacename = :workspaceName',
            [
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes(),
                'workspaceName' => $workspaceName->value,
            ],
            [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
            ]
        );
    }

    public function addWorkspace(WorkspaceName $workspaceName, ?WorkspaceName $baseWorkspaceName): void
    {
        $this->dbal->insert(
            $this->getWorkspacesTableName(),
            [
                'workspacename' => $workspaceName->value,
                'baseworkspacename' => $baseWorkspaceName?->value,
            ]
        );
    }

    public function updateWorkspace(WorkspaceName $workspaceName, ?WorkspaceName $baseWorkspaceName): void
    {
        $this->dbal->update(
            $this->getWorkspacesTableName(),
            [
                'baseworkspacename' => $baseWorkspaceName?->value,
            ],
            [
                'workspacename' => $workspaceName->value,
            ],
        );
    }

    public function removeWorkspace(WorkspaceName $workspaceName): void
    {
        $this->dbal->delete(
            $this->getWorkspacesTableName(),
            [
                'workspacename' => $workspaceName->value,
            ]
        );
    }

    /**
     * @param WorkspaceName $workspaceName
     * @return array<WorkspaceName>
     */
    public function getBaseWorkspaces(WorkspaceName $workspaceName): array
    {
        $baseWorkspaces = $this->dbal->executeQuery(
            'WITH RECURSIVE workspaceChain AS ('
            . 'SELECT * FROM ' . $this->getWorkspacesTableName() . ' w WHERE w.workspacename = :workspaceName'
            . ' UNION'
            . ' SELECT w.workspacename, w.baseworkspacename from ' . $this->getWorkspacesTableName() . ' w'
            . ' INNER JOIN workspaceChain  ON workspaceChain.baseworkspacename = w.workspacename'
            . ' )'
            . 'SELECT baseworkspacename FROM workspaceChain WHERE baseworkspacename is not null  ;',
            [
                'workspaceName' => $workspaceName->value
            ]
        )->fetchFirstColumn();

        return array_map(WorkspaceName::fromString(...), $baseWorkspaces);
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
        $this->dbal->executeStatement($platform->getTruncateTableSQL($this->getWorkspacesTableName()));
    }

    public function getTableNamePrefix(): string
    {
        return $this->tableNamePrefix;
    }

    private function getWorkspacesTableName(): string
    {
        return $this->tableNamePrefix . '_workspaces';
    }
}
