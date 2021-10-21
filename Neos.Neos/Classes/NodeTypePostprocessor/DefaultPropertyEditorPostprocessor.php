<?php
namespace Neos\Neos\NodeTypePostprocessor;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\NodeTypePostprocessor\NodeTypePostprocessorInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Utility\Arrays;
use Neos\Neos\Exception;

/**
 * Add default editor configurations for properties based on type and editor
 */
class DefaultPropertyEditorPostprocessor implements NodeTypePostprocessorInterface
{

    /**
     * @var array
     * @Flow\InjectConfiguration(package="Neos.Neos", path="userInterface.inspector.dataTypes")
     */
    protected $dataTypesDefaultConfiguration;

    /**
     * @var array
     * @Flow\InjectConfiguration(package="Neos.Neos", path="userInterface.inspector.editors")
     */
    protected $editorDefaultConfiguration;

    /**
     * @param NodeType $nodeType (uninitialized) The node type to process
     * @param array $configuration input configuration
     * @param array $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options): void
    {
        $nodeTypeName = $nodeType->getName();
        if (isset($configuration['properties']) && is_array($configuration['properties'])) {
            foreach ($configuration['properties'] as $propertyName => &$propertyConfiguration) {
                if (!isset($propertyConfiguration['type'])) {
                    continue;
                }

                $type = $propertyConfiguration['type'];

                if (!isset($propertyConfiguration['ui']['inspector'])) {
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
                    throw new Exception('Could not find editor for ' . $propertyName . ' in node type ' . $nodeTypeName, 1436809123);
                }

                // SECOND STEP: Build up the full inspector configuration by merging:
                // - take configuration from editor defaults
                // - take configuration from dataType
                // - take configuration from properties (NodeTypes)
                $mergedInspectorConfiguration = [];
                if (isset($this->editorDefaultConfiguration[$editor])) {
                    $mergedInspectorConfiguration = $this->editorDefaultConfiguration[$editor];
                }

                $mergedInspectorConfiguration = Arrays::arrayMergeRecursiveOverrule($mergedInspectorConfiguration, $defaultConfigurationFromDataType);
                $mergedInspectorConfiguration = Arrays::arrayMergeRecursiveOverrule($mergedInspectorConfiguration, $propertyConfiguration['ui']['inspector']);
                $propertyConfiguration['ui']['inspector'] = $mergedInspectorConfiguration;
                $propertyConfiguration['ui']['inspector']['editor'] = $editor;
            }
        }
    }
}
