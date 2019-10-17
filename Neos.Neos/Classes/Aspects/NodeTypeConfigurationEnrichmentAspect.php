<?php
namespace Neos\Neos\Aspects;

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
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Neos\Service\IconNameMappingService;
use Neos\Utility\Arrays;
use Neos\Neos\Exception;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class NodeTypeConfigurationEnrichmentAspect
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
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var IconNameMappingService
     */
    protected $iconNameMappingService;

    /**
     * @Flow\Around("method(Neos\ContentRepository\Domain\Model\NodeType->setFullConfiguration())")
     * @param JoinPointInterface $joinPoint
     * @return void
     * @throws Exception
     */
    public function enrichNodeTypeConfiguration(JoinPointInterface $joinPoint)
    {
        $configuration = $joinPoint->getMethodArgument('fullConfiguration');
        $nodeTypeName = $joinPoint->getProxy()->getName();

        $this->addEditorDefaultsToNodeTypeConfiguration($nodeTypeName, $configuration);
        $this->mapIconNames($configuration);

        $joinPoint->setMethodArgument('fullConfiguration', $configuration);
        $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * @Flow\Around("method(Neos\ContentRepository\Domain\Model\NodeType->__construct())")
     * @param JoinPointInterface $joinPoint
     * @return void
     */
    public function enrichNodeTypeLabelsConfiguration(JoinPointInterface $joinPoint)
    {
        $configuration = $joinPoint->getMethodArgument('configuration');
        $nodeTypeName = $joinPoint->getMethodArgument('name');

        $this->addLabelsToNodeTypeConfiguration($nodeTypeName, $configuration);

        $joinPoint->setMethodArgument('configuration', $configuration);
        $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * @param string $nodeTypeName
     * @param array $configuration
     * @return void
     */
    protected function addLabelsToNodeTypeConfiguration($nodeTypeName, array &$configuration)
    {
        if (isset($configuration['ui'])) {
            $this->setGlobalUiElementLabels($nodeTypeName, $configuration);
        }

        if (isset($configuration['properties'])) {
            $this->setPropertyLabels($nodeTypeName, $configuration);
        }
    }

    /**
     * Map all icon- prefixed icon names to the corresponding
     * names in the used icon implementation
     *
     * @param array $configuration
     */
    protected function mapIconNames(array &$configuration)
    {
        if (isset($configuration['ui']['icon'])) {
            $configuration['ui']['icon'] = $this->iconNameMappingService->convert($configuration['ui']['icon']);
        }

        $inspectorConfiguration = Arrays::getValueByPath($configuration, 'ui.inspector');
        if (is_array($inspectorConfiguration)) {
            foreach ($inspectorConfiguration as $elementTypeName => $elementTypeItems) {
                foreach ($elementTypeItems as $elementName => $elementConfiguration) {
                    if (isset($inspectorConfiguration[$elementTypeName][$elementName]['icon'])) {
                        $configuration['ui']['inspector'][$elementTypeName][$elementName]['icon'] = $this->iconNameMappingService->convert($inspectorConfiguration[$elementTypeName][$elementName]['icon']);
                    }
                }
            }
        }
    }

    /**
     * @param string $nodeTypeName
     * @param array $configuration
     * @throws Exception
     * @return void
     */
    protected function addEditorDefaultsToNodeTypeConfiguration($nodeTypeName, array &$configuration)
    {
        if (isset($configuration['properties']) && is_array($configuration['properties'])) {
            foreach ($configuration['properties'] as $propertyName => &$propertyConfiguration) {
                if (!isset($propertyConfiguration['type'])) {
                    continue;
                }

                $type = $propertyConfiguration['type'];

                if (!isset($this->dataTypesDefaultConfiguration[$type])) {
                    continue;
                }

                if (!isset($propertyConfiguration['ui']['inspector'])) {
                    continue;
                }

                $defaultConfigurationFromDataType = $this->dataTypesDefaultConfiguration[$type];

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

    /**
     * @param string $nodeTypeLabelIdPrefix
     * @param array $configuration
     * @return void
     */
    protected function setPropertyLabels($nodeTypeName, array &$configuration)
    {
        $nodeTypeLabelIdPrefix = $this->generateNodeTypeLabelIdPrefix($nodeTypeName);
        foreach ($configuration['properties'] as $propertyName => &$propertyConfiguration) {
            if (!isset($propertyConfiguration['ui'])) {
                continue;
            }

            if ($this->shouldFetchTranslation($propertyConfiguration['ui'])) {
                $propertyConfiguration['ui']['label'] = $this->getPropertyLabelTranslationId($nodeTypeLabelIdPrefix, $propertyName);
            }

            if (isset($propertyConfiguration['ui']['inspector']['editor']) && isset($propertyConfiguration['ui']['inspector']['editorOptions'])) {
                $translationIdGenerator = function ($path) use ($nodeTypeLabelIdPrefix, $propertyName) {
                    return $this->getPropertyConfigurationTranslationId($nodeTypeLabelIdPrefix, $propertyName, $path);
                };
                $this->applyEditorLabels($nodeTypeLabelIdPrefix, $propertyName, $propertyConfiguration['ui']['inspector']['editor'], $propertyConfiguration['ui']['inspector']['editorOptions'], $translationIdGenerator);
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
    protected function resolveHelpMessageThumbnail($nodeTypeName, $configurationThumbnail)
    {
        if ($nodeTypeName !== null) {
            $thumbnailUrl = '';
            if (isset($configurationThumbnail)) {
                $thumbnailUrl = $configurationThumbnail;
                if (strpos($thumbnailUrl, 'resource://') === 0) {
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
    }

    /**
     * @param string $nodeTypeLabelIdPrefix
     * @param string $propertyName
     * @param string $editorName
     * @param array $editorOptions
     * @param callable $translationIdGenerator
     * @return void
     */
    protected function applyEditorLabels($nodeTypeLabelIdPrefix, $propertyName, $editorName, array &$editorOptions, $translationIdGenerator)
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
     * Sets labels for global NodeType elements like tabs and groups and the general label.
     *
     * @param string $nodeTypeLabelIdPrefix
     * @param array $configuration
     * @return void
     */
    protected function setGlobalUiElementLabels($nodeTypeName, array &$configuration)
    {
        $nodeTypeLabelIdPrefix = $this->generateNodeTypeLabelIdPrefix($nodeTypeName);
        if ($this->shouldFetchTranslation($configuration['ui'])) {
            $configuration['ui']['label'] = $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, 'ui', 'label');
        }
        if (isset($configuration['ui']['help']['message']) && $this->shouldFetchTranslation($configuration['ui']['help'], 'message')) {
            $configuration['ui']['help']['message'] = $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, 'ui', 'help.message');
        }
        if (isset($configuration['ui']['help'])) {
            $configurationThumbnail = isset($configuration['ui']['help']['thumbnail']) ? $configuration['ui']['help']['thumbnail'] : null;
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
                if (isset($elementConfiguration['ui']['editor']) && isset($elementConfiguration['ui']['editorOptions'])) {
                    $translationIdGenerator = function ($path) use ($nodeTypeLabelIdPrefix, $elementName) {
                        return $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, 'creationDialog', $elementName . '.' . $path);
                    };
                    $this->applyEditorLabels($nodeTypeLabelIdPrefix, $elementName, $elementConfiguration['ui']['editor'], $elementConfiguration['ui']['editorOptions'], $translationIdGenerator);
                }
                if (!is_array($elementConfiguration) || !$this->shouldFetchTranslation($elementConfiguration['ui'])) {
                    continue;
                }
                $elementConfiguration['ui']['label'] = $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, 'creationDialog', $elementName);
            }
        }
    }

    /**
     * Should a label be generated for the given field or is there something configured?
     *
     * @param array $parentConfiguration
     * @param string $fieldName Name of the possibly existing subfield
     * @return boolean
     */
    protected function shouldFetchTranslation(array $parentConfiguration, $fieldName = 'label')
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
    protected function getInspectorElementTranslationId($nodeTypeSpecificPrefix, $elementType, $elementName)
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
    protected function getPropertyLabelTranslationId($nodeTypeSpecificPrefix, $propertyName)
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
    protected function getPropertyConfigurationTranslationId($nodeTypeSpecificPrefix, $propertyName, $labelPath)
    {
        return $nodeTypeSpecificPrefix . 'properties.' . $propertyName . '.' . $labelPath;
    }

    /**
     * Generates a label prefix for a specific node type with this format: "Vendor_Package:NodeTypes.NodeTypeName"
     *
     * @param string $nodeTypeName
     * @return string
     */
    protected function generateNodeTypeLabelIdPrefix($nodeTypeName)
    {
        $nodeTypeNameParts = explode(':', $nodeTypeName, 2);
        // in case the NodeType has just one section we default to 'Neos.Neos' as package as we don't have any further information.
        $packageKey = isset($nodeTypeNameParts[1]) ? $nodeTypeNameParts[0] : 'Neos.Neos';
        $nodeTypeName = isset($nodeTypeNameParts[1]) ? $nodeTypeNameParts[1] : $nodeTypeNameParts[0];

        return sprintf('%s:%s:', $packageKey, 'NodeTypes.' . $nodeTypeName);
    }

    /**
     * Splits an identifier string of the form PackageKey:id or PackageKey:Source:id into an array with the keys
     * id, source and packageKey.
     *
     * @param string $id translation id with possible package and source parts
     * @return array
     */
    protected function splitIdentifier($id)
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
