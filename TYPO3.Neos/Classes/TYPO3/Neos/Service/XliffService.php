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
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Service as LocalizationService;
use TYPO3\Flow\Utility\Unicode\Functions as UnicodeFunctions;

/**
 * The XLIFF service provides methods to find XLIFF files and parse them to json
 *
 * @Flow\Scope("singleton")
 */
class XliffService
{
    /**
     * A relative path for translations inside the package resources.
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
    protected $scrambleTranslatedLabels = false;

    /**
     * @Flow\InjectConfiguration(path="userInterface.translation.autoInclude", package="TYPO3.Neos")
     * @var array
     */
    protected $packagesRegisteredForAutoInclusion = [];

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * Return the json array for a given locale, sourceCatalog, xliffPath and package.
     * The json will be cached.
     *
     * @param Locale $locale The locale
     * @throws \TYPO3\Flow\I18n\Exception
     * @return \TYPO3\Flow\Error\Result
     */
    public function getCachedJson(Locale $locale)
    {
        $cacheIdentifier = md5($locale);

        if ($this->xliffToJsonTranslationsCache->has($cacheIdentifier)) {
            $json = $this->xliffToJsonTranslationsCache->get($cacheIdentifier);
        } else {
            $labels = [];
            $localeChain = $this->localizationService->getLocaleChain($locale);

            foreach ($this->packagesRegisteredForAutoInclusion as $packageKey => $sourcesToBeIncluded) {
                if (!is_array($sourcesToBeIncluded)) {
                    continue;
                }

                $translationBasePath = Files::concatenatePaths([
                    $this->packageManager->getPackage($packageKey)->getResourcesPath(),
                    $this->xliffBasePath
                ]);

                // We merge labels in the chain from the worst choice to best choice
                foreach (array_reverse($localeChain) as $allowedLocale) {
                    $localeSourcePath = Files::getNormalizedPath(Files::concatenatePaths([$translationBasePath, $allowedLocale]));
                    foreach ($sourcesToBeIncluded as $sourceName) {
                        foreach (glob($localeSourcePath . $sourceName . '.xlf') as $xliffPathAndFilename) {
                            $xliffPathInfo = pathinfo($xliffPathAndFilename);
                            $sourceName = str_replace($localeSourcePath, '', $xliffPathInfo['dirname'] . '/' . $xliffPathInfo['filename']);
                            $labels = Arrays::arrayMergeRecursiveOverrule($labels, $this->parseXliffToArray($xliffPathAndFilename, $packageKey, $sourceName));
                        }
                    }
                }
            }

            $json = json_encode($labels);
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
     * @todo remove the override handling once Flow takes care of that, see FLOW-61
     * @return array
     */
    public function parseXliffToArray($xliffPathAndFilename, $packageKey, $sourceName)
    {
        /** @var array $parsedData */
        $parsedData = $this->xliffParser->getParsedData($xliffPathAndFilename);
        $arrayData = array();
        foreach ($parsedData['translationUnits'] as $key => $value) {
            $valueToStore = !empty($value[0]['target']) ? $value[0]['target'] : $value[0]['source'];

            if ($this->scrambleTranslatedLabels) {
                $valueToStore = str_repeat('#', UnicodeFunctions::strlen($valueToStore));
            }

            $this->setArrayDataValue($arrayData, str_replace('.', '_', $packageKey) . '.' . str_replace('/', '_', $sourceName) . '.' . str_replace('.', '_', $key), $valueToStore);
        }

        return $arrayData;
    }

    /**
     * Helper method to create the needed json array from a dotted xliff id
     *
     * @param array $arrayPointer
     * @param string $key
     * @param string $value
     * @return void
     */
    protected function setArrayDataValue(array &$arrayPointer, $key, $value)
    {
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
