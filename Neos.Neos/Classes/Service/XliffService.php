<?php
namespace Neos\Neos\Service;

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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Error\Messages\Result;
use Neos\Flow\I18n\Exception;
use Neos\Flow\I18n\Xliff\Service\XliffFileProvider;
use Neos\Flow\I18n\Xliff\Service\XliffReader;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\Arrays;
use Neos\Utility\Files;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service as LocalizationService;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;

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
     * @var XliffReader
     */
    protected $xliffReader;

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
     * @Flow\InjectConfiguration(path="userInterface.scrambleTranslatedLabels", package="Neos.Neos")
     * @var boolean
     */
    protected $scrambleTranslatedLabels = false;

    /**
     * @Flow\InjectConfiguration(path="userInterface.translation.autoInclude", package="Neos.Neos")
     * @var array
     */
    protected $packagesRegisteredForAutoInclusion = [];

    /**
     * @Flow\Inject
     * @var XliffFileProvider
     */
    protected $xliffFileProvider;

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

            foreach ($this->packagesRegisteredForAutoInclusion as $packageKey => $sourcesToBeIncluded) {
                if (!is_array($sourcesToBeIncluded)) {
                    continue;
                }

                $package = $this->packageManager->getPackage($packageKey);
                $sources = $this->collectPackageSources($package, $sourcesToBeIncluded);

                //get the xliff files for those sources
                foreach ($sources as $sourceName) {
                    $fileId = $packageKey . ':' . $sourceName;
                    $file = $this->xliffFileProvider->getFile($fileId, $locale);

                    foreach ($file->getTranslationUnits() as $key => $value) {
                        $valueToStore = !empty($value[0]['target']) ? $value[0]['target'] : $value[0]['source'];
                        if ($this->scrambleTranslatedLabels) {
                            $valueToStore = str_repeat('#', UnicodeFunctions::strlen($valueToStore));
                        }
                        $this->setArrayDataValue($labels, str_replace('.', '_', $packageKey) . '.' . str_replace('/', '_', $sourceName) . '.' . str_replace('.', '_', $key), $valueToStore);
                    }
                }
            }

            $json = json_encode($labels);
            $this->xliffToJsonTranslationsCache->set($cacheIdentifier, $json);
        }

        return $json;
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
     * Collect all sources found in the given package as array (key = source, value = true)
     * If sourcesToBeIncluded is an array, only those sources are returned what match the wildcard-patterns in the
     * array-values
     *
     * @param PackageInterface $package
     * @param array $sourcesToBeIncluded optional array of wildcard-patterns to filter the sources
     * @return array
     */
    protected function collectPackageSources(PackageInterface $package, $sourcesToBeIncluded = null): array
    {
        $packageKey = $package->getPackageKey();
        $sources = [];
        $translationPath = $package->getResourcesPath() . $this->xliffBasePath;

        if (!is_dir($translationPath)) {
            return [];
        }

        foreach (Files::readDirectoryRecursively($translationPath, '.xlf') as $filePath) {
            //remove translation path from path
            $source = trim(str_replace($translationPath, '', $filePath), '/');
            //remove language part from path
            $source = trim(substr($source, strpos($source, '/')), '/');
            //remove file extension
            $source = substr($source, 0, strrpos($source, '.'));

            $this->xliffReader->readFiles($filePath,
                function (\XMLReader $file, $offset, $version) use ($packageKey, &$sources, $source, $sourcesToBeIncluded) {
                    $targetPackageKey = $packageKey;
                    if ($version === '1.2') {
                        //in xliff v1.2 the packageKey or source can be overwritten via attributes
                        $targetPackageKey = $file->getAttribute('product-name') ?: $packageKey;
                        $source = $file->getAttribute('original') ?: $source;
                    }
                    if ($packageKey !== $targetPackageKey) {
                        return;
                    }
                    if (is_array($sourcesToBeIncluded)) {
                        $addSource = false;
                        foreach ($sourcesToBeIncluded as $sourcePattern) {
                            if (fnmatch($sourcePattern, $source)) {
                                $addSource = true;
                                break;
                            }
                        }
                        if (!$addSource) {
                            return;
                        }
                    }
                    $sources[$source] = true;
                }
            );
        }
        return array_keys($sources);
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
