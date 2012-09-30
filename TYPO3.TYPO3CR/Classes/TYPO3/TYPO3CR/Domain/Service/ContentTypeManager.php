<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Manager for content types
 *
 * @Flow\Scope("singleton")
 */
class ContentTypeManager {

	/**
	 * Content types, indexed by name
	 *
	 * @var array
	 */
	protected $cachedContentTypes = array();

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Return all content types which have a certain $superType, without
	 * the $superType itself.
	 *
	 * @param string $superTypeName
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\ContentType> all content types registered in the system
	 */
	public function getSubContentTypes($superTypeName) {
		if ($this->cachedContentTypes === array()) {
			$this->loadContentTypes();
		}

		$filteredContentTypes = array();
		foreach ($this->cachedContentTypes as $contentTypeName => $contentType) {
			if ($contentType->isOfType($superTypeName) && $contentTypeName !== $superTypeName) {
				$filteredContentTypes[$contentTypeName] = $contentType;
			}
		}
		return $filteredContentTypes;
	}

	/**
	 * Returns the specified content type
	 *
	 * @param string $contentTypeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\ContentType or NULL
	 * @throws \TYPO3\TYPO3CR\Exception\ContentTypeNotFoundException
	 */
	public function getContentType($contentTypeName) {
		if ($this->cachedContentTypes === array()) {
			$this->loadContentTypes();
		}
		if (!isset($this->cachedContentTypes[$contentTypeName])) {
			throw new \TYPO3\TYPO3CR\Exception\ContentTypeNotFoundException('The content type "' . $contentTypeName . '" is not available.', 1316598370);
		}
		return $this->cachedContentTypes[$contentTypeName];
	}

	/**
	 * Checks if the specified content type exists
	 *
	 * @param string $contentTypeName Name of the content type
	 * @return boolean TRUE if it exists, otherwise FALSE
	 */
	public function hasContentType($contentTypeName) {
		if ($this->cachedContentTypes === array()) {
			$this->loadContentTypes();
		}
		return isset($this->cachedContentTypes[$contentTypeName]);
	}

	/**
	 * Creates a new content type
	 *
	 * @param string $contentTypeName Unique name of the new content type. Example: "TYPO3.TYPO3:Page"
	 * @return \TYPO3\TYPO3CR\Domain\Model\ContentType
	 * @throws \TYPO3\TYPO3CR\Exception
	 */
	public function createContentType($contentTypeName) {
		throw new \TYPO3\TYPO3CR\Exception('Creation of content types not supported so far; tried to create "' . $contentTypeName . '".', 1316449432);
	}

	/**
	 * Return the full configuration of all content types. This is just an internal
	 * method we need for exporting the schema to JavaScript for example.
	 *
	 * @return array
	 */
	public function getFullConfiguration() {
		if ($this->cachedContentTypes === array()) {
			$this->loadContentTypes();
		}
		$fullConfiguration = array();
		foreach ($this->cachedContentTypes as $contentTypeName => $contentType) {
			$fullConfiguration[$contentTypeName] = $contentType->getConfiguration();
		}
		return $fullConfiguration;
	}

	/**
	 * Loads all content types into memory.
	 *
	 * @return void
	 */
	protected function loadContentTypes() {
		foreach (array_keys($this->settings['contentTypes']) as $contentTypeName) {
			$this->loadContentType($contentTypeName);
		}
	}

	/**
	 * Load one content type, if it is not loaded yet.
	 *
	 * @param string $contentTypeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\ContentType
	 * @throws \TYPO3\TYPO3CR\Exception
	 */
	protected function loadContentType($contentTypeName) {
		if (isset($this->cachedContentTypes[$contentTypeName])) {
			return $this->cachedContentTypes[$contentTypeName];
		}

		if (!isset($this->settings['contentTypes'][$contentTypeName])) {
			throw new \TYPO3\TYPO3CR\Exception('Content type "' . $contentTypeName . '" does not exist', 1316451800);
		}

		$contentTypeConfiguration = $this->settings['contentTypes'][$contentTypeName];

		$mergedConfiguration = array();
		$superTypes = array();
		if (isset($contentTypeConfiguration['superTypes'])) {
			foreach ($contentTypeConfiguration['superTypes'] as $superTypeName) {
				$superType = $this->loadContentType($superTypeName);
				$superTypes[] = $superType;
				$mergedConfiguration = \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($mergedConfiguration, $superType->getConfiguration());
			}
			unset($mergedConfiguration['superTypes']);
		}
		$mergedConfiguration = \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($mergedConfiguration, $contentTypeConfiguration);

		$contentType = new \TYPO3\TYPO3CR\Domain\Model\ContentType($contentTypeName, $superTypes, $mergedConfiguration);

		$this->cachedContentTypes[$contentTypeName] = $contentType;
		return $contentType;
	}
}
?>