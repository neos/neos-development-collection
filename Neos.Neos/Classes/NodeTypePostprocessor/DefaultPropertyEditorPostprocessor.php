<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\NodeTypePostprocessor;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypePostprocessorInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Exception;
use Neos\Utility\Arrays;

/**
 * Add default editor configurations for properties based on type and editor
 */
class DefaultPropertyEditorPostprocessor implements NodeTypePostprocessorInterface
{
    /**
     * @var array<string,mixed>
     * @Flow\InjectConfiguration(package="Neos.Neos", path="userInterface.inspector.dataTypes")
     */
    protected $dataTypesDefaultConfiguration;

    /**
     * @var array<string,mixed>
     * @Flow\InjectConfiguration(package="Neos.Neos", path="userInterface.inspector.editors")
     */
    protected $editorDefaultConfiguration;

    /**
     * @param NodeType $nodeType (uninitialized) The node type to process
     * @param array<string,mixed> $configuration input configuration
     * @param array<string,mixed> $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options): void
    {
        $nodeTypeName = $nodeType->name->value;

        foreach ($configuration['references'] as $referenceName => &$referenceConfiguration) {
            if (!isset($referenceConfiguration['ui']['inspector'])) {
                // we presume that these are properties wich are not shown
                continue;
            }

            $editor = $referenceConfiguration['ui']['inspector']['editor'] ?? null;

            if (!$editor) {
                $maxAllowedItems = $referenceConfiguration['constraints']['maxItems'] ?? null;
                $editor = $maxAllowedItems === 1 ? 'Neos.Neos/Inspector/Editors/ReferenceEditor' : 'Neos.Neos/Inspector/Editors/ReferencesEditor';
            }

            $mergedInspectorConfiguration = $this->editorDefaultConfiguration[$editor] ?? [];
            $mergedInspectorConfiguration = Arrays::arrayMergeRecursiveOverrule(
                $mergedInspectorConfiguration,
                $referenceConfiguration['ui']['inspector']
            );
            $referenceConfiguration['ui']['inspector'] = $mergedInspectorConfiguration;
            $referenceConfiguration['ui']['inspector']['editor'] = $editor;
        }

        if (isset($configuration['properties']) && is_array($configuration['properties'])) {
            foreach ($configuration['properties'] as $propertyName => &$propertyConfiguration) {
                if (!isset($propertyConfiguration['type'])) {
                    continue;
                }

                $type = $propertyConfiguration['type'];

                if (!isset($propertyConfiguration['ui']['inspector'])) {
                    // we presume that these are properties wich are not shown
                    continue;
                }

                $defaultConfigurationFromDataType = $this->dataTypesDefaultConfiguration[$type] ?? [];

                // FIRST STEP: Figure out which editor should be used
                // - Default: editor as configured from the data type
                // - Override: editor as configured from the property configuration.
                if (isset($propertyConfiguration['ui']['inspector']['editor'])) {
                    $editor = $propertyConfiguration['ui']['inspector']['editor'];
                } elseif (isset($defaultConfigurationFromDataType['editor'])) {
                    $editor = $defaultConfigurationFromDataType['editor'];
                } else {
                    throw new Exception(
                        'Could not find editor for ' . $propertyName . ' in node type ' . $nodeTypeName,
                        1436809123
                    );
                }

                // SECOND STEP: Build up the full inspector configuration by merging:
                // - take configuration from editor defaults
                // - take configuration from dataType
                // - take configuration from properties (NodeTypes)
                $mergedInspectorConfiguration = $this->editorDefaultConfiguration[$editor] ?? [];
                $mergedInspectorConfiguration = Arrays::arrayMergeRecursiveOverrule(
                    $mergedInspectorConfiguration,
                    $defaultConfigurationFromDataType
                );
                $mergedInspectorConfiguration = Arrays::arrayMergeRecursiveOverrule(
                    $mergedInspectorConfiguration,
                    $propertyConfiguration['ui']['inspector']
                );
                $propertyConfiguration['ui']['inspector'] = $mergedInspectorConfiguration;
                $propertyConfiguration['ui']['inspector']['editor'] = $editor;
            }
        }
    }
}
