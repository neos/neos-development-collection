<?php
namespace TYPO3\TYPO3\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Generate a schema in JSON format for the VIE dataTypes validation, necessary
 * when using nodes as semantic types.
 *
 * Example schema: http://schema.rdfs.org/all.json
 */
class ContentTypeSchemaBuilder {

	/**
	 * The config array for TYPO3CR from yaml file
	 *
	 * @var array
	 */
	protected $configArray;

	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var array
	 */
	protected $types = array();

	/**
	 * @var object
	 */
	protected $configuration;

	/**
	 * @var array
	 */
	protected $superTypeConfiguration = array();

	/**
	 * Takes the configArray from TYPO3CR
	 *
	 * @param array $configArray
	 */
	public function __construct(array $configArray) {
		$this->configArray = $configArray;
	}

	/**
	 * Converts the $configArray property to a fully structured array
	 * in the same structure as the schema to be created.
	 *
	 * @return void
	 */
	public function convertToVieSchema() {
		foreach ($this->configArray['contentTypes'] as $contentType => $contentTypeConfiguration) {
			$this->superTypeConfiguration['typo3:' . $contentType] = array();
			if (isset($contentTypeConfiguration['superTypes']) && is_array($contentTypeConfiguration['superTypes'])) {
				foreach ($contentTypeConfiguration['superTypes'] as $superType) {
					$this->superTypeConfiguration['typo3:' . $contentType][] = 'typo3:' . $superType;
				}
			}

			$contentTypeProperties = array();

			if (isset($contentTypeConfiguration['properties'])) {
				foreach ($contentTypeConfiguration['properties'] as $property => $propertyConfiguration) {

						// TODO Make sure we can configure the range for all multi column elements to define what types a column may contain
					$this->addProperty('typo3:' . $contentType, 'typo3:' . $property, $propertyConfiguration);
					$contentTypeProperties[] = 'typo3:' . $property;
				}
			}

			$metadata = array();
			$metaDataPropertyIndexes = array('group', 'icon', 'inlineEditableProperties', 'darkIcon');
			foreach ($metaDataPropertyIndexes as $propertyName) {
				if (isset($contentTypeConfiguration[$propertyName])) {
					$metadata[$propertyName] = $contentTypeConfiguration[$propertyName];
				}
			}

			$this->types['typo3:' . $contentType] = (object) array(
				'label' => isset($contentTypeConfiguration['label']) ? $contentTypeConfiguration['label'] : $contentType,
				'id' => 'typo3:' . $contentType,
				'properties' => array(),
				'specific_properties' => $contentTypeProperties,
				'subtypes' => array(),
				'metadata' => (object) $metadata,
				'supertypes' => $this->superTypeConfiguration['typo3:' . $contentType],
				'url' => 'http://www.typo3.org/ns/2011/Flow/Packages/TYPO3/Content/',
				'ancestors' => array(),
				'comment' => '',
				'comment_plain' => ''
			);
		}

		unset($this->types['typo3:unstructured']);

		foreach ($this->types as $contentType => $contentTypeDefinition) {
			$this->types[$contentType]->subtypes = $this->getAllSubtypes($contentType);
			$this->types[$contentType]->ancestors = $this->getAllAncestors($contentType);

			$this->removeUndeclaredTypes($this->types[$contentType]->supertypes);
			$this->removeUndeclaredTypes($this->types[$contentType]->ancestors);
		}

		foreach ($this->properties as $property => $propertyConfiguration) {
			if (isset($propertyConfiguration->domains) && is_array($propertyConfiguration->domains)) {
				foreach ($propertyConfiguration->domains as $domain) {
					if (preg_match('/TYPO3\.Phoenix\.ContentTypes:.*Column/', $domain)) {
						$this->properties[$property]->ranges = array_keys($this->types);
					}
				}
			}
		}

			// Convert the TYPO3.Phoenix.ContentTypes:Section element to support content-collection
			// TODO Move to content type definition
		if (isset($this->types['typo3:TYPO3.Phoenix.ContentTypes:Section'])) {
			$this->addProperty('typo3:TYPO3.Phoenix.ContentTypes:Section', 'typo3:content-collection', array());
			$this->types['typo3:TYPO3.Phoenix.ContentTypes:Section']->specific_properties[] = 'typo3:content-collection';
			$this->properties['typo3:content-collection']->ranges = array_keys($this->types);
		}

		$this->configuration = (object) array(
			'types' => (object) $this->types,
			'properties' => (object) $this->properties,
		);
	}

	/**
	 * Generate the configArray as a JSON data
	 *
	 * @return string $configJson
	 */
	public function generateAsJson() {
		return json_encode($this->configuration);
	}

	/**
	 * Adds a property to the list of known properties
	 *
	 * @param string $contentType
	 * @param string $propertyName
	 * @param array $propertyConfiguration
	 * @return void
	 */
	protected function addProperty($contentType, $propertyName, array $propertyConfiguration) {
		if (isset($this->properties[$propertyName])) {
			$this->properties[$propertyName]->domains[] = $contentType;
		} else {
			$this->properties[$propertyName] = (object) array(
				'comment' => isset($propertyConfiguration['label']) ? $propertyConfiguration['label'] : $propertyName,
				'comment_plain' => isset($propertyConfiguration['label']) ? $propertyConfiguration['label'] : $propertyName,
				'domains' => array($contentType),
				'id' => $propertyName,
				'label' => $propertyName,
				'ranges' => array(),
				'min' => 0,
				'max' => -1
			);
		}
	}

	/**
	 * Cleans up all types which are not know in given configuration array
	 *
	 * @param array $configuration
	 * @return void
	 */
	protected function removeUndeclaredTypes(array &$configuration) {
		foreach ($configuration as $index => $type) {
			if (!isset($this->types[$type])) {
				unset($configuration[$index]);
			}
		}
	}

	/**
	 * Return all sub content types of a content type (recursively)
	 *
	 * @param string $type
	 * @return array
	 */
	protected function getAllSubtypes($type) {
		$subTypes = array();

		foreach ($this->superTypeConfiguration as $contentType => $superTypes) {
			if (in_array($type, $superTypes)) {
				if (isset($this->types[$contentType])) {
					$subTypes[] = $contentType;

					$contentTypeSubTypes = $this->getAllSubtypes($contentType);
					foreach ($contentTypeSubTypes as $contentTypeSubType) {

						if (!in_array($contentTypeSubType, $subTypes)) {
							$subTypes[] = $contentTypeSubType;
						}
					}
				}
			}
		}

		return $subTypes;
	}

	/**
	 * Return all ancestors of a content type
	 *
	 * @param string $type
	 * @return array
	 */
	protected function getAllAncestors($type) {
		if (!isset($this->superTypeConfiguration[$type])) {
			return array();
		}
		$ancestors = $this->superTypeConfiguration[$type];

		foreach ($this->superTypeConfiguration[$type] as $currentSuperType) {
			if (isset($this->types[$currentSuperType])) {
				$currentSuperTypeAncestors = $this->getAllAncestors($currentSuperType);
				$ancestors = array_merge($ancestors, $currentSuperTypeAncestors);
			}
		}

		return $ancestors;
	}

}
?>