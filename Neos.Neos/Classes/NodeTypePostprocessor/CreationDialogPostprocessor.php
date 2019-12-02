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

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\NodeTypePostprocessor\NodeTypePostprocessorInterface;
use Neos\Utility\Arrays;
use Neos\Utility\PositionalArraySorter;

/**
 * Node Type post processor that looks for properties flagged with "showInCreationDialog" and sets the "creationDialog" configuration accordingly
 *
 * Example NodeTypes.yaml configuration:
 *
 * 'Some.Node:Type':
 *   # ...
 *   properties:
 *     'someProperty':
 *       type: string
 *       ui:
 *         label: 'Link'
 *         showInCreationDialog: true
 *         inspector:
 *           editor: 'Neos.Neos/Inspector/Editors/LinkEditor'
 *
 * Will be converted to:
 *
 * 'Some.Node:Type':
 *   # ...
 *   ui:
 *     creationDialog:
 *       elements:
 *         'someProperty':
 *           type: string
 *           ui:
 *             label: 'Link'
 *             editor: 'Neos.Neos/Inspector/Editors/LinkEditor'
 *   properties:
 *     'someProperty':
 *       # ...
 */
class CreationDialogPostprocessor implements NodeTypePostprocessorInterface
{
    /**
     * @param NodeType $nodeType (uninitialized) The node type to process
     * @param array $configuration input configuration
     * @param array $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options): void
    {
        if (!isset($configuration['properties'])) {
            return;
        }
        $creationDialogElements = [];
        foreach ($configuration['properties'] as $propertyName => $propertyConfiguration) {
            if (!isset($propertyConfiguration['ui']['showInCreationDialog']) || $propertyConfiguration['ui']['showInCreationDialog'] !== true) {
                continue;
            }
            $creationDialogElement = $this->convertPropertyConfiguration($nodeType->getConfiguration('properties.' . $propertyName) ?? []);
            if (isset($configuration['ui']['creationDialog']['elements'][$propertyName])) {
                $creationDialogElement = Arrays::arrayMergeRecursiveOverrule($creationDialogElement, $configuration['ui']['creationDialog']['elements'][$propertyName]);
            }
            $creationDialogElements[$propertyName] = $creationDialogElement;
        }
        $configuration['ui']['creationDialog']['elements'] = (new PositionalArraySorter($creationDialogElements))->toArray();
    }

    /**
     * Converts a NodeType property configuration to the corresponding creationDialog "element" configuration
     *
     * @param array $propertyConfiguration
     * @return array
     */
    private function convertPropertyConfiguration(array $propertyConfiguration): array
    {
        $convertedConfiguration = $propertyConfiguration;
        unset($convertedConfiguration['ui']['inspector']);
        $editor = $propertyConfiguration['ui']['inspector']['editor'] ?? null;
        $editorOptions = $propertyConfiguration['ui']['inspector']['editorOptions'] ?? [];

        // The following editors don't (completely) work in the Creation Dialog so they are disabled
        // TODO should be adjusted if fixed. See https://github.com/neos/neos-ui/issues/1034
        $unsupportedEditors = ['Neos.Neos/Inspector/Editors/ImageEditor', 'Neos.Neos/Inspector/Editors/AssetEditor', 'Neos.Neos/Inspector/Editors/RichTextEditor', 'Neos.Neos/Inspector/Editors/CodeEditor'];
        if (\in_array($editor, $unsupportedEditors, true)) {
            $convertedConfiguration['ui']['help']['message'] = sprintf('The "%s" editor is currently not supported in the Creation Dialog', $editor);
            $editorOptions['disabled'] = true;
        }
        $convertedConfiguration['ui']['editor'] = $editor;
        $convertedConfiguration['ui']['editorOptions'] = $editorOptions;
        return $convertedConfiguration;
    }
}
