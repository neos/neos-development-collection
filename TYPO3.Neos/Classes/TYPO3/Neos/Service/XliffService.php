<?php
namespace TYPO3\Neos\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\Frontend\VariableFrontend;
use Neos\Error\Messages\Result;
use Neos\Flow\I18n\Exception;
use Neos\Flow\I18n\Xliff\XliffParser;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\Arrays;
use Neos\Utility\Files;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service as LocalizationService;
use Neos\Flow\Utility\Unicode\Functions as UnicodeFunctions;

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
     * @return Result
     * @throws Exception
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
     * @return array
     *
     * @todo remove the override handling once Flow takes care of that, see FLOW-61
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
     * @return integer The current cache version identifier
     */
    public function getCacheVersion()
    {
        $version = $this->xliffToJsonTranslationsCache->get('ConfigurationVersion');
        if ($version === false) {
            $version = time();
            $this->xliffToJsonTranslationsCache->set('ConfigurationVersion', (string)$version);
        }
        return $version;
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
