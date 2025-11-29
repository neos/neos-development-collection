<?php

declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Processors;

use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Export\Asset\AssetExporter;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Severity;
use Neos\Media\Domain\Model\ResourceBasedInterface;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;

final class AssetExportProcessor implements ProcessorInterface
{
    /**
     * @var array<string, true>
     */
    private array $processedAssetIds = [];

    /**
     * @param iterable<int, array<string, mixed>> $nodeDataRows
     */
    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly AssetExporter $assetExporter,
        private readonly iterable $nodeDataRows,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        foreach ($this->nodeDataRows as $nodeDataRow) {
            if ($nodeDataRow['path'] === '/sites') {
                // the sites node has no properties and is unstructured
                continue;
            }
            $nodeTypeName = NodeTypeName::fromString($nodeDataRow['nodetype']);
            $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
            if (!$nodeType) {
                $context->dispatch(Severity::ERROR, "The node type \"{$nodeTypeName->value}\" is not available. Node: \"{$nodeDataRow['identifier']}\"");
                continue;
            }
            try {
                $properties = json_decode($nodeDataRow['properties'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $context->dispatch(Severity::ERROR, "Failed to JSON-decode properties {$nodeDataRow['properties']} of node \"{$nodeDataRow['identifier']}\" (type: \"{$nodeTypeName->value}\"): {$exception->getMessage()}");
                continue;
            }
            foreach ($properties as $propertyName => $propertyValue) {
                if ($nodeType->hasReference($propertyName)) {
                    $context->dispatch(Severity::NOTICE, "Skipped node data processing for the property \"{$propertyName}\". The property is a reference. (Node: {$nodeDataRow['identifier']})");
                    continue;
                }
                try {
                    $propertyType = $nodeType->getPropertyType($propertyName);
                } catch (\InvalidArgumentException) {
                    $context->dispatch(Severity::WARNING, "Skipped node data processing for the property \"{$propertyName}\". The property name is not part of the NodeType schema for the NodeType \"{$nodeType->name->value}\". (Node: {$nodeDataRow['identifier']})");
                    continue;
                }
                foreach ($this->extractAssetIdentifiers($propertyType, $propertyValue) as $assetId) {
                    if (array_key_exists($assetId, $this->processedAssetIds)) {
                        continue;
                    }
                    $this->processedAssetIds[$assetId] = true;
                    try {
                        $this->assetExporter->exportAsset($assetId);
                    } catch (\Exception $exception) {
                        $context->dispatch(Severity::ERROR, "Failed to extract assets of property \"{$propertyName}\" of node \"{$nodeDataRow['identifier']}\" (type: \"{$nodeTypeName->value}\"): {$exception->getMessage()}");
                    }
                }
            }
        }
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
            preg_match_all('/asset:\/\/(?<assetId>[\w-]*)/i', (string)$value, $matches, PREG_SET_ORDER);
            return array_map(static fn(array $match) => $match['assetId'], $matches);
        }
        if (is_subclass_of($type, ResourceBasedInterface::class, true)) {
            return isset($value['__identifier']) ? [$value['__identifier']] : [];
        }

        // Collection type?
        /** @var array{type: string, elementType: string|null, nullable: bool} $parsedType */
        $parsedType = TypeHandling::parseType($type);
        if ($parsedType['elementType'] === null) {
            return [];
        }
        if (!is_subclass_of($parsedType['elementType'], ResourceBasedInterface::class) && !is_subclass_of($parsedType['elementType'], \Stringable::class)) {
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
}
