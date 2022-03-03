<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Projector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\ESCR\AssetUsage\Dto\AssetIdsByProperty;
use Neos\ESCR\AssetUsage\Dto\AssetUsage;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\AssetUsage\Dto\AssetUsages;

/**
 * @Flow\Scope("singleton")
 *
 * @internal Not meant to be used in user land code. In order to look up asset usages the AssetUsageFinder can be used
 */
final class AssetUsageRepository
{

    private const TABLE_NAME = 'neos_neos_projection_asset_usage';

    public function __construct(
        private readonly Connection $dbal
    ) {}

    public function findUsages(AssetUsageFilter $filter): AssetUsages
    {
        $queryBuilder = $this->dbal->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME);
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
        return new AssetUsages(function() use ($queryBuilder) {
            $result = $queryBuilder->execute();
            if (!$result instanceof Result) {
                throw new \RuntimeException(sprintf('Expected instance of "%s", got: "%s"', Result::class, get_debug_type($result)), 1646320966);
            }
            /** @var array{assetidentifier: string, contentstreamidentifier: string, origindimensionspacepointhash: string, nodeaggregateidentifier: string, propertyname: string} $row */
            foreach ($result->iterateAssociative() as $row) {
                yield new AssetUsage($row['assetidentifier'], ContentStreamIdentifier::fromString($row['contentstreamidentifier']), $row['origindimensionspacepointhash'], NodeAggregateIdentifier::fromString($row['nodeaggregateidentifier']), $row['propertyname']);
            }
        }, function() use ($queryBuilder) {
            /** @var string $count */
            $count = $this->dbal->fetchOne('SELECT COUNT(*) FROM (' . $queryBuilder->getSQL() . ') s', $queryBuilder->getParameters());
            return (int)$count;
        });
    }

    public function addUsagesForNode(NodeAddress $nodeAddress, AssetIdsByProperty $assetIdsByProperty): void
    {
        if ($assetIdsByProperty->hasPropertiesWithoutAssets()) {
            $this->dbal->executeStatement('DELETE FROM ' . self::TABLE_NAME . ' WHERE contentStreamIdentifier = :contentStreamIdentifier AND nodeAggregateIdentifier = :nodeAggregateIdentifier AND originDimensionSpacePointHash = :originDimensionSpacePointHash AND propertyName IN (:propertyNames)', [
                'contentStreamIdentifier' => $nodeAddress->getContentStreamIdentifier(),
                'nodeAggregateIdentifier' => $nodeAddress->getNodeAggregateIdentifier(),
                'originDimensionSpacePointHash' => $nodeAddress->getDimensionSpacePoint()->getHash(),
                'propertyNames' => $assetIdsByProperty->propertyNamesWithoutAsset(),
            ], [
                'propertyNames' => Connection::PARAM_STR_ARRAY,
            ]);
        }
        foreach ($assetIdsByProperty as $propertyName => $assetIdentifiers) {
            foreach ($assetIdentifiers as $assetIdentifier) {
                try {
                    $this->dbal->insert(self::TABLE_NAME, [
                        'assetIdentifier' => $assetIdentifier,
                        'contentStreamIdentifier' => $nodeAddress->getContentStreamIdentifier(),
                        'nodeAggregateIdentifier' => $nodeAddress->getNodeAggregateIdentifier(),
                        'originDimensionSpacePointHash' => $nodeAddress->getDimensionSpacePoint()->getHash(),
                        'propertyName' => $propertyName,
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    // A usage already exists for this node and property -> can be ignored
                }
            }
        }
    }

    public function removeContentStream(ContentStreamIdentifier $contentStreamIdentifier): void
    {
        $this->dbal->delete(self::TABLE_NAME, ['contentStreamIdentifier' => $contentStreamIdentifier]);
    }

    public function copyContentStream(ContentStreamIdentifier $sourceContentStreamIdentifier, ContentStreamIdentifier $targetContentStreamIdentifier): void
    {
        $this->dbal->executeStatement('INSERT INTO ' . self::TABLE_NAME . ' SELECT assetidentifier, :targetContentStreamIdentifier contentstreamidentifier, nodeaggregateidentifier, origindimensionspacepointhash, propertyname FROM ' . self::TABLE_NAME . ' WHERE contentStreamIdentifier = :sourceContentStreamIdentifier', [
            'sourceContentStreamIdentifier' => $sourceContentStreamIdentifier,
            'targetContentStreamIdentifier' => $targetContentStreamIdentifier,
        ]);
    }

    public function copyDimensions(OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint, OriginDimensionSpacePoint $targetOriginDimensionSpacePoint): void
    {
        try {
            $this->dbal->executeStatement('INSERT INTO ' . self::TABLE_NAME . ' SELECT assetidentifier, contentstreamidentifier, nodeaggregateidentifier, :targetOriginDimensionSpacePointHash origindimensionspacepointhash, propertyname FROM ' . self::TABLE_NAME . ' WHERE originDimensionSpacePointHash = :sourceOriginDimensionSpacePointHash', [
                'sourceOriginDimensionSpacePointHash' => $sourceOriginDimensionSpacePoint->getHash(),
                'targetOriginDimensionSpacePointHash' => $targetOriginDimensionSpacePoint->getHash(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // A usage already exists for this node and property -> can be ignored
        }
    }

    public function remove(AssetUsage $usage): void
    {
        $this->dbal->delete(self::TABLE_NAME, [
            'assetIdentifier' => $usage->assetIdentifier,
            'contentStreamIdentifier' => $usage->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $usage->nodeAggregateIdentifier,
            'originDimensionSpacePointHash' => $usage->originDimensionSpacePoint,
            'propertyName' => $usage->propertyName,
        ]);
    }

    public function removeAsset(string $assetIdentifier): void
    {
        $this->dbal->delete(self::TABLE_NAME, [
            'assetIdentifier' => $assetIdentifier,
        ]);
    }

    public function removeNode(NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePointSet $dimensionSpacePoints): void
    {
        $this->dbal->executeStatement('DELETE FROM ' . self::TABLE_NAME . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier AND originDimensionSpacePointHash IN (:dimensionSpacePointHashes)', [
            'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
            'dimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes(),
        ], [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
        ]);
    }

    /**
     * @throws DbalException
     */
    public function reset(): void
    {
        /** @var AbstractPlatform|null $platform */
        $platform = $this->dbal->getDatabasePlatform();
        if ($platform === null) {
            throw new \RuntimeException(sprintf('Failed to determine database platform for database "%s"', $this->dbal->getDatabase()), 1645781464);
        }
        $this->dbal->executeStatement($platform->getTruncateTableSQL(self::TABLE_NAME));
    }
}
