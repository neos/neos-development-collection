<?php
namespace TYPO3\Neos\Service;

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
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\I18n\Xliff\XliffParser;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Service as LocalizationService;
use TYPO3\Flow\Utility\Unicode\Functions as UnicodeFunctions;

/**
 * The XLIFF service provides methods to find XLIFF files and parse them to json
 *
 * @Flow\Scope("singleton")
 */
class XliffService {

	/**
	 * An absolute path to the directory where translation files reside.
	 *
	 * @var string
	 */
	protected $xliffBasePath = 'Private/Translations/';

	/**
	 * @Flow\Inject
	 * @var XliffParser
	 */
	protected $xliffParser;

	/**
	 * @Flow\Inject
	 * @var LocalizationService
	 */
	protected $localizationService;

	/**
	 * @Flow\Inject
	 * @var VariableFrontend
	 */
	protected $xliffToJsonTranslationsCache;

	/**
	 * @Flow\InjectConfiguration(path="userInterface.scrambleTranslatedLabels", package="TYPO3.Neos")
	 * @var boolean
	 */
	protected $scrambleTranslatedLabels = FALSE;

	/**
	 * Return the json array for a given locale, sourceCatalog, xliffPath and package.
	 * The json will be cached.
	 *
	 * @param Locale $locale The locale
	 * @param string $sourceName The source catalog to use
	 * @param string $packageKey The package
	 * @throws \TYPO3\Flow\I18n\Exception
	 * @return \TYPO3\Flow\Error\Result
	 */
	public function getCachedJson(Locale $locale, $sourceName = 'Main', $packageKey = 'TYPO3.Neos') {
		$cacheIdentifier = md5(implode('#', array($locale, $sourceName, $packageKey)));

		if ($this->xliffToJsonTranslationsCache->has($cacheIdentifier)) {
			$json = $this->xliffToJsonTranslationsCache->get($cacheIdentifier);
		} else {
			$sourcePath = Files::concatenatePaths(array('resource://' . $packageKey, $this->xliffBasePath));
			list($xliffPathAndFilename) = $this->localizationService->getXliffFilenameAndPath($sourcePath, $sourceName, $locale);

			$json = $this->parseXliffToJson($xliffPathAndFilename, $packageKey, $sourceName);

			$this->xliffToJsonTranslationsCache->set($cacheIdentifier, $json);
		}

		return $json;
	}

	/**
	 * Read the xliff file and create the desired json
	 *
	 * @param string $xliffPathAndFilename The file to read
	 * @param string $packageKey
	 * @param string $sourceName
	 * @return string
	 */
	public function parseXliffToJson($xliffPathAndFilename, $packageKey, $sourceName) {
		/** @var array $parsedData */
		$parsedData = $this->xliffParser->getParsedData($xliffPathAndFilename);
		$arrayData = array();
		foreach ($parsedData['translationUnits'] as $key => $value) {
			$valueToStore = $value[0]['target'] ? $value[0]['target'] : $value[0]['source'];

			if ($this->scrambleTranslatedLabels) {
				$valueToStore = str_repeat('#', UnicodeFunctions::strlen($valueToStore));
			}

			$this->setArrayDataValue($arrayData, $packageKey . '.' . $sourceName . '.' . str_replace ('.', '-', $key), $valueToStore);
		}

		return json_encode($arrayData);
	}

	/**
	 * Helper method to create the needed json array from a dotted xliff id
	 *
	 * @param array $arrayPointer
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	protected function setArrayDataValue(array &$arrayPointer, $key, $value) {
		$keys = explode('.', $key);

		// Extract the last key
		$lastKey = array_pop($keys);

		// Walk/build the array to the specified key
		while ($arrayKey = array_shift($keys)) {
			if (!array_key_exists($arrayKey, $arrayPointer)) {
				$arrayPointer[$arrayKey] = array();
			}
			$arrayPointer = &$arrayPointer[$arrayKey];
		}

		// Set the final key
		$arrayPointer[$lastKey] = $value;
	}

}