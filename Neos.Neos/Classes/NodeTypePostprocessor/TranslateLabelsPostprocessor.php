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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Utility\Arrays;

class TranslateLabelsPostprocessor implements NodeTypePostprocessorInterface
{

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @param NodeType $nodeType (uninitialized) The node type to process
     * @param array $configuration input configuration
     * @param array $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options): void
    {
        $configuration = $nodeType->getLocalConfiguration();
        if (isset($configuration['ui'])) {
            $this->setGlobalUiElementLabels($nodeType->getName(), $configuration);
        }

        if (isset($configuration['properties'])) {
            $this->setPropertyLabels($nodeType->getName(), $configuration);
        }
    }

    /**
     * Sets labels for global NodeType elements like tabs and groups and the general label.
     *
     * @param string $nodeTypeName
     * @param array $configuration
     * @return void
     */
    private function setGlobalUiElementLabels(string $nodeTypeName, array &$configuration): void
    {
        $nodeTypeLabelIdPrefix = $this->generateNodeTypeLabelIdPrefix($nodeTypeName);
        if ($this->shouldFetchTranslation($configuration['ui'])) {
            $configuration['ui']['label'] = $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, 'ui', 'label');
        }
        if (isset($configuration['ui']['help']['message']) && $this->shouldFetchTranslation($configuration['ui']['help'], 'message')) {
            $configuration['ui']['help']['message'] = $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, 'ui', 'help.message');
        }
        if (isset($configuration['ui']['help'])) {
            $configurationThumbnail = $configuration['ui']['help']['thumbnail'] ?? null;
            $thumbnailUrl = $this->resolveHelpMessageThumbnail($nodeTypeName, $configurationThumbnail);
            if ($thumbnailUrl !== '') {
                $configuration['ui']['help']['thumbnail'] = $thumbnailUrl;
            }
        }

        $inspectorConfiguration = Arrays::getValueByPath($configuration, 'ui.inspector');
        if (is_array($inspectorConfiguration)) {
            foreach ($inspectorConfiguration as $elementTypeName => $elementTypeItems) {
                foreach ($elementTypeItems as $elementName => $elementConfiguration) {
                    if (!is_array($elementConfiguration) || !$this->shouldFetchTranslation($elementConfiguration)) {
                        continue;
                    }

                    $translationLabelId = $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, $elementTypeName, $elementName);
                    $configuration['ui']['inspector'][$elementTypeName][$elementName]['label'] = $translationLabelId;
                }
            }
        }

        $creationDialogConfiguration = Arrays::getValueByPath($configuration, 'ui.creationDialog.elements');
        if (is_array($creationDialogConfiguration)) {
            $creationDialogConfiguration = &$configuration['ui']['creationDialog']['elements'];
            foreach ($creationDialogConfiguration as $elementName => &$elementConfiguration) {
                if (isset($elementConfiguration['ui']['editor'], $elementConfiguration['ui']['editorOptions'])) {
                    $translationIdGenerator = function ($path) use ($nodeTypeLabelIdPrefix, $elementName) {
                        return $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, 'creationDialog', $elementName . '.' . $path);
                    };
                    $this->applyEditorLabels($elementConfiguration['ui']['editor'], $elementConfiguration['ui']['editorOptions'], $translationIdGenerator);
                }
                if (!is_array($elementConfiguration) || !$this->shouldFetchTranslation($elementConfiguration['ui'])) {
                    continue;
                }
                $elementConfiguration['ui']['label'] = $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, 'creationDialog', $elementName);
            }
        }
    }

    /**
     * @param string $nodeTypeName
     * @param array $configuration
     * @return void
     */
    protected function setPropertyLabels(string $nodeTypeName, array &$configuration): void
    {
        $nodeTypeLabelIdPrefix = $this->generateNodeTypeLabelIdPrefix($nodeTypeName);
        foreach ($configuration['properties'] as $propertyName => &$propertyConfiguration) {
            if (!isset($propertyConfiguration['ui'])) {
                continue;
            }

            if ($this->shouldFetchTranslation($propertyConfiguration['ui'])) {
                $propertyConfiguration['ui']['label'] = $this->getPropertyLabelTranslationId($nodeTypeLabelIdPrefix, $propertyName);
            }

            if (isset($propertyConfiguration['ui']['inspector']['editor'], $propertyConfiguration['ui']['inspector']['editorOptions'])) {
                $translationIdGenerator = function ($path) use ($nodeTypeLabelIdPrefix, $propertyName) {
                    return $this->getPropertyConfigurationTranslationId($nodeTypeLabelIdPrefix, $propertyName, $path);
                };
                $this->applyEditorLabels($propertyConfiguration['ui']['inspector']['editor'], $propertyConfiguration['ui']['inspector']['editorOptions'], $translationIdGenerator);
            }

            if (isset($propertyConfiguration['ui']['aloha']) && $this->shouldFetchTranslation($propertyConfiguration['ui']['aloha'], 'placeholder')) {
                $propertyConfiguration['ui']['aloha']['placeholder'] = $this->getPropertyConfigurationTranslationId($nodeTypeLabelIdPrefix, $propertyName, 'aloha.placeholder');
            }

            if (isset($propertyConfiguration['ui']['inline']['editorOptions']) && $this->shouldFetchTranslation($propertyConfiguration['ui']['inline']['editorOptions'], 'placeholder')) {
                $propertyConfiguration['ui']['inline']['editorOptions']['placeholder'] = $this->getPropertyConfigurationTranslationId($nodeTypeLabelIdPrefix, $propertyName, 'ui.inline.editorOptions.placeholder');
            }

            if (isset($propertyConfiguration['ui']['help']['message']) && $this->shouldFetchTranslation($propertyConfiguration['ui']['help'], 'message')) {
                $propertyConfiguration['ui']['help']['message'] = $this->getPropertyConfigurationTranslationId($nodeTypeLabelIdPrefix, $propertyName, 'ui.help.message');
            }
        }
    }

    /**
     * Resolve help message thumbnail url
     *
     * @param string $nodeTypeName
     * @param string $configurationThumbnail
     * @return string $thumbnailUrl
     */
    protected function resolveHelpMessageThumbnail(string $nodeTypeName, $configurationThumbnail): string
    {
        if ($nodeTypeName === null) {
            return '';
        }
        $thumbnailUrl = '';
        if (isset($configurationThumbnail)) {
            $thumbnailUrl = $configurationThumbnail;
            if (strncmp($thumbnailUrl, 'resource://', 11) === 0) {
                $thumbnailUrl = $this->resourceManager->getPublicPackageResourceUriByPath($thumbnailUrl);
            }
        } else {
            # look in well know location
            $splitPrefix = $this->splitIdentifier($nodeTypeName);
            $relativePathAndFilename = 'NodeTypes/Thumbnails/' . $splitPrefix['id'] . '.png';
            $resourcePath = 'resource://' . $splitPrefix['packageKey'] . '/Public/' . $relativePathAndFilename;
            if (file_exists($resourcePath)) {
                $thumbnailUrl = $this->resourceManager->getPublicPackageResourceUriByPath($resourcePath);
            }
        }
        return $thumbnailUrl;
    }

    /**
     * @param string $editorName
     * @param array $editorOptions
     * @param callable $translationIdGenerator
     * @return void
     */
    protected function applyEditorLabels(string $editorName, array &$editorOptions, callable $translationIdGenerator): void
    {
        switch ($editorName) {
            case 'Neos.Neos/Inspector/Editors/SelectBoxEditor':
                if (isset($editorOptions) && $this->shouldFetchTranslation($editorOptions, 'placeholder')) {
                    $editorOptions['placeholder'] = $translationIdGenerator('selectBoxEditor.placeholder');
                }

                if (!isset($editorOptions['values']) || !is_array($editorOptions['values'])) {
                    break;
                }
                foreach ($editorOptions['values'] as $value => &$optionConfiguration) {
                    if ($optionConfiguration === null) {
                        continue;
                    }
                    if ($this->shouldFetchTranslation($optionConfiguration)) {
                        $optionConfiguration['label'] = $translationIdGenerator('selectBoxEditor.values.' . $value);
                    }
                }
                unset($optionConfiguration);
                break;
            case 'Neos.Neos/Inspector/Editors/CodeEditor':
                if ($this->shouldFetchTranslation($editorOptions, 'buttonLabel')) {
                    $editorOptions['buttonLabel'] = $translationIdGenerator('codeEditor.buttonLabel');
                }
                break;
            case 'Neos.Neos/Inspector/Editors/TextFieldEditor':
                if (isset($editorOptions) && $this->shouldFetchTranslation($editorOptions, 'placeholder')) {
                    $editorOptions['placeholder'] = $translationIdGenerator('textFieldEditor.placeholder');
                }
                break;
            case 'Neos.Neos/Inspector/Editors/TextAreaEditor':
                if (isset($editorOptions) && $this->shouldFetchTranslation($editorOptions, 'placeholder')) {
                    $editorOptions['placeholder'] = $translationIdGenerator('textAreaEditor.placeholder');
                }
                break;
        }
    }

    /**
     * Should a label be generated for the given field or is there something configured?
     *
     * @param array $parentConfiguration
     * @param string $fieldName Name of the possibly existing subfield
     * @return boolean
     */
    protected function shouldFetchTranslation(array $parentConfiguration, string $fieldName = 'label'): bool
    {
        $fieldValue = array_key_exists($fieldName, $parentConfiguration) ? $parentConfiguration[$fieldName] : '';

        return (trim($fieldValue) === 'i18n');
    }

    /**
     * Generates a generic inspector element label with the given $nodeTypeSpecificPrefix.
     *
     * @param string $nodeTypeSpecificPrefix
     * @param string $elementType
     * @param string $elementName
     * @return string
     */
    protected function getInspectorElementTranslationId(string $nodeTypeSpecificPrefix, string $elementType, string $elementName): string
    {
        return $nodeTypeSpecificPrefix . $elementType . '.' . $elementName;
    }

    /**
     * Generates a property label with the given $nodeTypeSpecificPrefix.
     *
     * @param string $nodeTypeSpecificPrefix
     * @param string $propertyName
     * @return string
     */
    protected function getPropertyLabelTranslationId(string $nodeTypeSpecificPrefix, string $propertyName): string
    {
        return $nodeTypeSpecificPrefix . 'properties.' . $propertyName;
    }

    /**
     * Generates a property configuration-label with the given $nodeTypeSpecificPrefix.
     *
     * @param string $nodeTypeSpecificPrefix
     * @param string $propertyName
     * @param string $labelPath
     * @return string
     */
    protected function getPropertyConfigurationTranslationId(string $nodeTypeSpecificPrefix, string $propertyName, string $labelPath): string
    {
        return $nodeTypeSpecificPrefix . 'properties.' . $propertyName . '.' . $labelPath;
    }

    /**
     * Generates a label prefix for a specific node type with this format: "Vendor_Package:NodeTypes.NodeTypeName"
     *
     * @param string $nodeTypeName
     * @return string
     */
    protected function generateNodeTypeLabelIdPrefix(string $nodeTypeName): string
    {
        $nodeTypeNameParts = explode(':', $nodeTypeName, 2);
        // in case the NodeType has just one section we default to 'Neos.Neos' as package as we don't have any further information.
        $packageKey = isset($nodeTypeNameParts[1]) ? $nodeTypeNameParts[0] : 'Neos.Neos';
        $nodeTypeName = $nodeTypeNameParts[1] ?? $nodeTypeNameParts[0];

        return sprintf('%s:%s:', $packageKey, 'NodeTypes.' . $nodeTypeName);
    }

    /**
     * Splits an identifier string of the form PackageKey:id or PackageKey:Source:id into an array with the keys
     * id, source and packageKey.
     *
     * @param string $id translation id with possible package and source parts
     * @return array
     */
    protected function splitIdentifier(string $id): array
    {
        $packageKey = 'Neos.Neos';
        $source = 'Main';
        $idParts = explode(':', $id, 3);
        switch (count($idParts)) {
            case 2:
                $packageKey = $idParts[0];
                $id = $idParts[1];
                break;
            case 3:
                $packageKey = $idParts[0];
                $source = str_replace('.', '/', $idParts[1]);
                $id = $idParts[2];
                break;
        }
        return [
            'id' => $id,
            'source' => $source,
            'packageKey' => $packageKey
        ];
    }
}
