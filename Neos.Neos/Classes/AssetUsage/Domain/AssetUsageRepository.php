<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Domain;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Result;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsages;

/**
 * @internal Not meant to be used in user land code. In order to look up asset usages the AssetUsageService or GlobalAssetUsageService can be used
 */
final class AssetUsageRepository
{
    public const TABLE = 'neos_asset_usage';

    public function __construct(
        private readonly Connection $dbal,
    ) {
    }

    public function findUsages(ContentRepositoryId $contentRepositoryId, AssetUsageFilter $filter): AssetUsages
    {
        $queryBuilder = $this->dbal->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from(self::TABLE);
        $queryBuilder->andWhere('contentrepositoryid = :contentRepositoryId');
        $queryBuilder->setParameter('contentRepositoryId', $contentRepositoryId->value);
        if ($filter->hasAssetId()) {
            if ($filter->includeVariantsOfAsset === true) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq('assetid', ':assetId'),
                        $queryBuilder->expr()->eq('originalassetid', ':assetId'),
                    )
                );
            } else {
                $queryBuilder->andWhere('assetid = :assetId');
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
        if ($filter->groupByNodeAggregate) {
            $queryBuilder->addGroupBy('nodeaggregateid');
        }
        if ($filter->groupByWorkspaceName) {
            $queryBuilder->addGroupBy('workspacename');
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
            /** @var array{contentrepositoryid: string,assetid: string, workspacename: string, origindimensionspacepointhash: string, origindimensionspacepoint: string, nodeaggregateid: string, propertyname: string} $row */
            foreach ($result->iterateAssociative() as $row) {
                yield new AssetUsage(
                    ContentRepositoryId::fromString($row['contentrepositoryid']),
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

    /**
     * @param WorkspaceName[] $workspaceNames
     * @return array<AssetUsage>
     */
    public function findUsageForNodeInWorkspaces(ContentRepositoryId $contentRepositoryId, Node $node, array $workspaceNames): array
    {
        $sql = <<<SQL
            SELECT * FROM {$this->getTableName()}
            WHERE
                contentrepositoryid = :contentRepositoryId
                AND nodeaggregateid = :nodeAggregateId
                AND origindimensionspacepointhash = :originDimensionSpacePointHash
                AND workspacename in (:workspaceNames)
        SQL;

        $result = $this->dbal->executeQuery($sql, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'nodeAggregateId' => $node->aggregateId->value,
            'originDimensionSpacePointHash' => $node->dimensionSpacePoint->hash,
            'workspaceNames' => array_map(fn ($workspaceName) => $workspaceName->value, $workspaceNames),
        ], [
            'propertyNames' => Connection::PARAM_STR_ARRAY,
            'workspaceNames' => Connection::PARAM_STR_ARRAY,
        ]);

        $usages = [];
        foreach ($result->iterateAssociative() as $row) {
            $usages[] = new AssetUsage(
                ContentRepositoryId::fromString($row['contentrepositoryid']),
                $row['assetid'],
                WorkspaceName::fromString($row['workspacename']),
                OriginDimensionSpacePoint::fromJsonString($row['origindimensionspacepoint']),
                NodeAggregateId::fromString($row['nodeaggregateid']),
                $row['propertyname']
            );
        }
        return $usages;
    }

    public function addUsagesForNodeWithAssetOnProperty(ContentRepositoryId $contentRepositoryId, Node $node, string $propertyName, string $assetId, ?string $originalAssetId = null): void
    {
        try {
            $this->dbal->insert(self::TABLE, [
                'contentrepositoryid' => $contentRepositoryId->value,
                'assetid' => $assetId,
                'originalassetid' => $originalAssetId,
                'workspacename' => $node->workspaceName->value,
                'nodeaggregateid' => $node->aggregateId->value,
                'origindimensionspacepoint' => $node->dimensionSpacePoint->toJson(),
                'origindimensionspacepointhash' => $node->dimensionSpacePoint->hash,
                'propertyname' => $propertyName,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // A usage already exists for this node and property -> can be ignored
        }
    }

    public function updateAssetUsageDimensionSpacePoint(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, DimensionSpacePoint $source, DimensionSpacePoint $target): void
    {
        $this->dbal->update($this->getTableName(), [
            'origindimensionspacepoint' => $target->toJson(),
            'origindimensionspacepointhash' => $target->hash,
        ], [
            'contentrepositoryid' => $contentRepositoryId->value,
            'workspacename' => $workspaceName->value,
            'origindimensionspacepointhash' => $source->hash,
        ]);
    }

    public function removeAssetUsagesOfWorkspace(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
    ): void {
        $sql = <<<SQL
            DELETE FROM {$this->getTableName()}
             WHERE contentrepositoryid = :contentRepositoryId
             AND workspacename = :workspaceName
        SQL;

        $this->dbal->executeStatement($sql, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'workspaceName' => $workspaceName->value,
        ]);
    }

    public function removeAssetUsagesOfWorkspaceWithAllProperties(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        $sql = <<<SQL
            DELETE FROM {$this->getTableName()}
             WHERE contentrepositoryid = :contentRepositoryId
             AND workspacename = :workspaceName
             AND nodeAggregateId = :nodeAggregateId
             AND originDimensionSpacePointHash = :originDimensionSpacePointHash
        SQL;

        $this->dbal->executeStatement($sql, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'workspaceName' => $workspaceName->value,
            'nodeAggregateId' => $nodeAggregateId->value,
            'originDimensionSpacePointHash' => $dimensionSpacePoint->hash,
        ]);
    }

    /**
     * @param WorkspaceName[] $workspaceNames
     */
    public function removeAssetUsagesForNodeAggregateIdAndDimensionSpacePointWithAssetOnPropertyInWorkspaces(
        ContentRepositoryId $contentRepositoryId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
        string $propertyName,
        string $assetId,
        array $workspaceNames
    ): void {
        $sql = <<<SQL
                DELETE FROM {$this->getTableName()}
                WHERE contentrepositoryid = :contentRepositoryId
                    AND workspacename in (:workspaceNames)
                    AND nodeaggregateid = :nodeAggregateId
                    AND origindimensionspacepointhash = :originDimensionSpacePointHash
                    AND propertyname = :propertyName
                    AND assetId = :assetId
            SQL;

        $this->dbal->executeStatement($sql, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'nodeAggregateId' => $nodeAggregateId->value,
            'originDimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'workspaceNames' => array_map(fn ($workspaceName) => $workspaceName->value, $workspaceNames),
            'propertyName' => $propertyName,
            'assetId' => $assetId,
        ], [
            'workspaceNames' => Connection::PARAM_STR_ARRAY,
        ]);
    }

    public function remove(AssetUsage $usage): void
    {
        $this->dbal->delete(self::TABLE, [
            'contentrepositoryid' => $usage->contentRepositoryId->value,
            'assetid' => $usage->assetId,
            'workspacename' => $usage->workspaceName->value,
            'nodeaggregateid' => $usage->nodeAggregateId->value,
            'origindimensionspacepointhash' => $usage->originDimensionSpacePoint->hash,
            'propertyname' => $usage->propertyName,
        ]);
    }

    public function removeAsset(ContentRepositoryId $contentRepositoryId, string $assetId): void
    {
        // TODO: What about OriginalAssetId?
        $this->dbal->delete(self::TABLE, [
            'contentrepositoryid' => $contentRepositoryId->value,
            'assetId' => $assetId,
        ]);
    }

    public function removeAll(ContentRepositoryId $contentRepositoryId): void
    {
        $this->dbal->delete(self::TABLE, [
            'contentrepositoryid' => $contentRepositoryId->value,
        ]);
    }

    private function getTableName(): string
    {
        return self::TABLE;
    }
}
