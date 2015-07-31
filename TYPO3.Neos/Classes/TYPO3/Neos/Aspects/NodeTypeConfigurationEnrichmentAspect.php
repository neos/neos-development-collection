<?php
namespace TYPO3\Neos\Aspects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Utility\Arrays;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class NodeTypeConfigurationEnrichmentAspect {

	/**
	 * @var array
	 * @Flow\InjectConfiguration(package="TYPO3.Neos", path="userInterface.inspector.dataTypes")
	 */
	protected $dataTypesDefaultConfiguration;

	/**
	 * @var array
	 * @Flow\InjectConfiguration(package="TYPO3.Neos", path="userInterface.inspector.editors")
	 */
	protected $editorDefaultConfiguration;

	/**
	 * @Flow\Around("method(TYPO3\TYPO3CR\Domain\Model\NodeType->__construct())")
	 * @return void
	 */
	public function enrichNodeTypeConfiguration(JoinPointInterface $joinPoint) {
		$configuration = $joinPoint->getMethodArgument('configuration');
		$nodeTypeName = $joinPoint->getMethodArgument('name');

		$this->addEditorDefaultsToNodeTypeConfiguration($nodeTypeName, $configuration);
		$this->addLabelsToNodeTypeConfiguration($nodeTypeName, $configuration);

		$joinPoint->setMethodArgument('configuration', $configuration);
		$joinPoint->getAdviceChain()->proceed($joinPoint);
	}

	/**
	 * @param string $nodeTypeName
	 * @param array $configuration
	 * @return void
	 */
	protected function addLabelsToNodeTypeConfiguration($nodeTypeName, &$configuration) {
		$nodeTypeLabelIdPrefix = $this->generateNodeTypeLabelIdPrefix($nodeTypeName);

		if (isset($configuration['ui'])) {
			$this->setGlobalUiElementLabels($nodeTypeLabelIdPrefix, $configuration);
		}

		if (isset($configuration['properties'])) {
			$this->setPropertyLabels($nodeTypeLabelIdPrefix, $configuration);
		}
	}

	/**
	 * @param string $nodeTypeName
	 * @param array $configuration
	 * @throws \TYPO3\Neos\Exception
	 * @return void
	 */
	protected function addEditorDefaultsToNodeTypeConfiguration($nodeTypeName, &$configuration) {
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
					throw new \TYPO3\Neos\Exception('Could not find editor for ' . $propertyName . ' in node type ' . $nodeTypeName, 1436809123);
				}

				// SECOND STEP: Build up the full inspector configuration by merging:
				// - take configuration from editor defaults
				// - take configuration from dataType
				// - take configuration from properties (NodeTypes)
				$mergedInspectorConfiguration = array();
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
	protected function setPropertyLabels($nodeTypeLabelIdPrefix, &$configuration) {
		foreach ($configuration['properties'] as $propertyName => &$propertyConfiguration) {
			if (!isset($propertyConfiguration['ui'])) {
				continue;
			}

			if ($this->shouldGenerateLabel($propertyConfiguration['ui'])) {
				$propertyConfiguration['ui']['label'] = $this->getPropertyLabelTranslationId($nodeTypeLabelIdPrefix, $propertyName);
			}

			if (isset($propertyConfiguration['ui']['inspector']['editor'])) {
				$this->applyInspectorEditorLabels($nodeTypeLabelIdPrefix, $propertyName, $propertyConfiguration);
			}
		}
	}

	/**
	 * @param string $nodeTypeLabelIdPrefix
	 * @param string $propertyName
	 * @param array $propertyConfiguration
	 * @return void
	 */
	protected function applyInspectorEditorLabels($nodeTypeLabelIdPrefix, $propertyName, &$propertyConfiguration) {
		$editorName = $propertyConfiguration['ui']['inspector']['editor'];

		switch ($editorName) {
			case 'TYPO3.Neos/Inspector/Editors/SelectBoxEditor':
				if (isset($propertyConfiguration['ui']['inspector']['editorOptions']) && $this->shouldGenerateLabel($propertyConfiguration['ui']['inspector']['editorOptions'], 'placeholder')) {
					$propertyConfiguration['ui']['inspector']['editorOptions']['placeholder'] = $this->getPropertyConfigurationTranslationId($nodeTypeLabelIdPrefix, $propertyName, 'selectBoxEditor.placeholder');
				}

				if (!isset($propertyConfiguration['ui']['inspector']['editorOptions']['values']) || !is_array($propertyConfiguration['ui']['inspector']['editorOptions']['values'])) {
					break;
				}
				foreach ($propertyConfiguration['ui']['inspector']['editorOptions']['values'] as $value => &$optionConfiguration) {
					if ($optionConfiguration === NULL) {
						continue;
					}
					if ($this->shouldGenerateLabel($optionConfiguration)) {
						$optionConfiguration['label'] = $this->getPropertyConfigurationTranslationId($nodeTypeLabelIdPrefix, $propertyName, 'selectBoxEditor.values.' . $value);
					}
				}
				break;
			case 'TYPO3.Neos/Inspector/Editors/CodeEditor':
				if ($this->shouldGenerateLabel($propertyConfiguration['ui']['inspector']['editorOptions'], 'buttonLabel')) {
					$propertyConfiguration['ui']['inspector']['editorOptions']['buttonLabel'] = $this->getPropertyConfigurationTranslationId($nodeTypeLabelIdPrefix, $propertyName, 'codeEditor.buttonLabel');
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
	protected function setGlobalUiElementLabels($nodeTypeLabelIdPrefix, &$configuration) {
		if ($this->shouldGenerateLabel($configuration['ui'])) {
			$configuration['ui']['label'] = $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, 'ui', 'label');
		}

		$inspectorConfiguration = Arrays::getValueByPath($configuration, 'ui.inspector');
		if (is_array($inspectorConfiguration)) {
			foreach ($inspectorConfiguration as $elementTypeName => $elementTypeItems) {
				foreach ($elementTypeItems as $elementName => $elementConfiguration) {
					if (!$this->shouldGenerateLabel($elementConfiguration)) {
						continue;
					}

					$translationLabelId = $this->getInspectorElementTranslationId($nodeTypeLabelIdPrefix, $elementTypeName, $elementName);
					$configuration['ui']['inspector'][$elementTypeName][$elementName]['label'] = $translationLabelId;
				}
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
	protected function shouldGenerateLabel($parentConfiguration, $fieldName = 'label') {
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
	protected function getInspectorElementTranslationId($nodeTypeSpecificPrefix, $elementType, $elementName) {
		return $nodeTypeSpecificPrefix . $elementType . '.' . $elementName;
	}

	/**
	 * Generates a property label with the given $nodeTypeSpecificPrefix.
	 *
	 * @param string $nodeTypeSpecificPrefix
	 * @param string $propertyName
	 * @return string
	 */
	protected function getPropertyLabelTranslationId($nodeTypeSpecificPrefix, $propertyName) {
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
	protected function getPropertyConfigurationTranslationId($nodeTypeSpecificPrefix, $propertyName, $labelPath) {
		return $nodeTypeSpecificPrefix . 'properties.' . $propertyName . '.' . $labelPath;
	}

	/**
	 * Generates a label prefix for a specific node type with this format: "Vendor_Package:NodeTypes.NodeTypeName"
	 *
	 * @param string $nodeTypeName
	 * @return string
	 */
	protected function generateNodeTypeLabelIdPrefix($nodeTypeName) {
		$nodeTypeNameParts = explode(':', $nodeTypeName, 2);
		// in case the NodeType has just one section we default to 'TYPO3.Neos' as package as we don't have any further information.
		$packageKey = isset($nodeTypeNameParts[1]) ? $nodeTypeNameParts[0] : 'TYPO3.Neos';
		$nodeTypeName = isset($nodeTypeNameParts[1]) ? $nodeTypeNameParts[1] : $nodeTypeNameParts[0];

		return sprintf('%s:%s:', $packageKey, 'NodeTypes.' . $nodeTypeName);
	}
}