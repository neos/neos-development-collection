<?php
/** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration;

use Neos\ContentRepository\Export\Asset\AssetExporter;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\Media\Domain\Model\ResourceBasedInterface;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;

final class NodeDataToAssetsProcessor implements ProcessorInterface
{
    private array $processedAssetIds = [];
    private array $callbacks = [];

    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly AssetExporter $assetExporter,
        private readonly iterable $nodeDataRows,
    ) {}

    public function onMessage(\Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    public function run(): ProcessorResult
    {
        $numberOfErrors = 0;
        foreach ($this->nodeDataRows as $nodeDataRow) {
            $nodeTypeName = NodeTypeName::fromString($nodeDataRow['nodetype']);
            try {
                $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
            } catch (NodeTypeNotFoundException $exception) {
                $numberOfErrors ++;
                $this->dispatch(Severity::ERROR, '%s. Node: "%s"', $exception->getMessage(), $nodeDataRow['identifier']);
                continue;
            }
            // HACK the following line is required in order to fully initialize the node type
            $nodeType->getFullConfiguration();
            try {
                $properties = json_decode($nodeDataRow['properties'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $numberOfErrors ++;
                $this->dispatch(Severity::ERROR, 'Failed to JSON-decode properties %s of node "%s" (type: "%s"): %s', $nodeDataRow['properties'], $nodeDataRow['identifier'], $nodeTypeName, $exception->getMessage());
                continue;
            }
            foreach ($properties as $propertyName => $propertyValue) {
                $propertyType = $nodeType->getPropertyType($propertyName);
                foreach ($this->extractAssetIdentifiers($propertyType, $propertyValue) as $assetId) {
                    if (array_key_exists($assetId, $this->processedAssetIds)) {
                        continue;
                    }
                    $this->processedAssetIds[$assetId] = true;
                    try {
                        $this->assetExporter->exportAsset($assetId);
                    } catch (\Exception $exception) {
                        $numberOfErrors ++;
                        $this->dispatch(Severity::ERROR, 'Failed to extract assets of property "%s" of node "%s" (type: "%s"): %s', $propertyName, $nodeDataRow['identifier'], $nodeTypeName, $exception->getMessage());
                    }
                }
            }
        }
        $numberOfExportedAssets = count($this->processedAssetIds);
        $this->processedAssetIds = [];
        return ProcessorResult::success(sprintf('Exported %d asset%s. Errors: %d', $numberOfExportedAssets, $numberOfExportedAssets === 1 ? '' : 's', $numberOfErrors));
    }

    /** ----------------------------- */

    /**
     * @param string $type
     * @param mixed $value
     * @return array<string>
     * @throws InvalidTypeException
     */
    private function extractAssetIdentifiers(string $type, mixed $value): array
    {
        if (($type === 'string' || is_subclass_of($type, \Stringable::class, true)) && is_string($value)) {
            // @phpstan-ignore-next-line
            preg_match_all('/asset:\/\/(?<assetId>[\w-]*)/i', (string)$value, $matches, PREG_SET_ORDER);
            return array_map(static fn(array $match) => $match['assetId'], $matches);
        }
        if (is_subclass_of($type, ResourceBasedInterface::class, true)) {
            // @phpstan-ignore-next-line
            return isset($value['__identifier']) ? [$value['__identifier']] : [];
        }

        // Collection type?
        /** @var array{type: string, elementType: string|null, nullable: bool} $parsedType */
        $parsedType = TypeHandling::parseType($type);
        if ($parsedType['elementType'] === null) {
            return [];
        }
        if (!is_subclass_of($parsedType['elementType'], ResourceBasedInterface::class, true)
            && !is_subclass_of($parsedType['elementType'], \Stringable::class, true)) {
            return [];
        }
        /** @var array<array<string>> $assetIdentifiers */
        $assetIdentifiers = [];
        /** @var iterable<mixed> $value */
        foreach ($value as $elementValue) {
            $assetIdentifiers[] = $this->extractAssetIdentifiers($parsedType['elementType'], $elementValue);
        }
        return array_merge(...$assetIdentifiers);
    }

    private function dispatch(Severity $severity, string $message, mixed ...$args): void
    {
        $renderedMessage = sprintf($message, ...$args);
        foreach ($this->callbacks as $callback) {
            $callback($severity, $renderedMessage);
        }
    }

}
