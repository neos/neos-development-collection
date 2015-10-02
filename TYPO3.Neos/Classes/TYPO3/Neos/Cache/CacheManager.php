<?php
namespace TYPO3\Neos\Cache;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A cache manager for Neos caches
 *
 * @Flow\Scope("singleton")
 */
class CacheManager
{
    /**
     * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
     */
    protected $configurationCache;

    /**
     * Get the version of the configuration cache
     *
     * This value will be changed on every change of configuration files or relevant domain
     * model changes (Site or Domain).
     *
     * @return string
     */
    public function getConfigurationCacheVersion()
    {
        $version = $this->configurationCache->get('ConfigurationVersion');
        if ($version === false) {
            $version = time();
            $this->configurationCache->set('ConfigurationVersion', (string)$version);
        }
        return $version;
    }

    /**
     * @param \TYPO3\Flow\Cache\Frontend\StringFrontend $configurationCache
     */
    public function setConfigurationCache($configurationCache)
    {
        $this->configurationCache = $configurationCache;
    }
}
