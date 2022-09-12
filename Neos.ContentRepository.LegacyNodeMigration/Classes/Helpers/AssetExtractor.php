<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Media\Domain\Model\Asset;
use Neos\Utility\ObjectAccess;

final class AssetExtractor
{
    private array $processedAssetIds = [];

    public function __construct(
        private readonly \Closure $findAssetByIdentifier,
    ) {}

    /**
     * @param iterable<array> $nodeDataRows
     */
    public function run(iterable $nodeDataRows): iterable
    {
        $this->resetRuntimeState();

        foreach ($nodeDataRows as $nodeDataRow) {
            $nodeTypeName = NodeTypeName::fromString($nodeDataRow['nodetype']);
            try {
                // Note: We use a PostgreSQL platform because the implementation is forward-compatible, @see JsonArrayType::convertToPHPValue()
                $decodedProperties = (new JsonArrayType())->convertToPHPValue($nodeDataRow['properties'], new PostgreSQL100Platform());
            } catch (\Throwable $e) {
                throw new MigrationException(sprintf('Failed to decode properties %s of node "%s" (type: "%s"): %s', json_encode($nodeDataRow['properties']), $nodeDataRow['identifier'], $nodeTypeName, $e->getMessage()), 1656057030, $e);
            }
            if (!is_array($decodedProperties)) {
                throw new MigrationException(sprintf('Failed to decode properties %s of node "%s" (type: "%s")', json_encode($nodeDataRow['properties']), $nodeDataRow['identifier'], $nodeTypeName), 1656057035);
            }
            foreach ($decodedProperties as $propertyName => $propertyValue) {
                try {
                    yield from $this->extractAssets($propertyValue);
                } catch (\Throwable $e) {
                    throw new MigrationException(sprintf('Failed to extract assets from property "%s" of node "%s" (type: "%s"): %s', $propertyName, $nodeDataRow['identifier'], $nodeTypeName, $e->getMessage()), 1656931260, $e);
                }
            }
        }
    }

    /** ----------------------------- */

    private function extractAssets(mixed $propertyValue): iterable
    {
        if ($propertyValue instanceof Asset) {
            $assetId = $propertyValue->getIdentifier();
            \Neos\Flow\var_dump($assetId, '$assetId');
            if ($assetId === null) {
                // TODO exception/error
                return;
            }
            if (!isset($this->processedAssetIds[$assetId])) {
                $this->processedAssetIds[$assetId] = true;
                yield $propertyValue;
            }
            return;
        }
        if (is_iterable($propertyValue)) {
            foreach ($propertyValue as $singlePropertyValue) {
                yield from $this->extractAssets($singlePropertyValue);
            }
        }
        if (!(is_string($propertyValue) || $propertyValue instanceof \Stringable)) {
            return;
        }
        preg_match_all('/asset:\/\/(?<assetId>[\w-]*)/i', (string)$propertyValue, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $assetId = $match['assetId'];
            $asset = ($this->findAssetByIdentifier)($assetId);
            \Neos\Flow\var_dump($asset, '$asset ' . $assetId);
            if ($asset === null) {
                // TODO exception/error
                continue;
            }
            yield from $this->extractAssets($asset);
        }
    }

    private function resetRuntimeState(): void
    {
        $this->processedAssetIds = [];
    }

}
