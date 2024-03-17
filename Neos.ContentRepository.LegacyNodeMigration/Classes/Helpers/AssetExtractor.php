<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Media\Domain\Model\Asset;

final class AssetExtractor
{
    /**
     * @var array<string, true>
     */
    private array $processedAssetIds = [];

    public function __construct(
        private readonly \Closure $findAssetByIdentifier,
    ) {}

    /**
     * @param iterable<int, array<string, mixed>> $nodeDataRows
     * @return iterable<int, Asset>
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
                throw new MigrationException(sprintf('Failed to decode properties %s of node "%s" (type: "%s"): %s', json_encode($nodeDataRow['properties']), $nodeDataRow['identifier'], $nodeTypeName->value, $e->getMessage()), 1656057030, $e);
            }
            if (!is_array($decodedProperties)) {
                throw new MigrationException(sprintf('Failed to decode properties %s of node "%s" (type: "%s")', json_encode($nodeDataRow['properties']), $nodeDataRow['identifier'], $nodeTypeName->value), 1656057035);
            }
            foreach ($decodedProperties as $propertyName => $propertyValue) {
                try {
                    yield from $this->extractAssets($propertyValue);
                } catch (\Throwable $e) {
                    throw new MigrationException(sprintf('Failed to extract assets from property "%s" of node "%s" (type: "%s"): %s', $propertyName, $nodeDataRow['identifier'], $nodeTypeName->value, $e->getMessage()), 1656931260, $e);
                }
            }
        }
    }

    /** ----------------------------- */

    /**
     * @return iterable<int, Asset>
     */
    private function extractAssets(mixed $propertyValue): iterable
    {
        if ($propertyValue instanceof Asset) {
            /** @var string|null $assetId */
            $assetId = $propertyValue->getIdentifier();
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
