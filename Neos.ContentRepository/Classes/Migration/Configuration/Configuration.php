<?php
namespace Neos\ContentRepository\Migration\Configuration;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Migration\Exception\MigrationException;

/**
 * Abstract Migration Configuration as a base for different configuration sources.
 */
abstract class Configuration implements ConfigurationInterface
{
    /**
     * @var array
     */
    protected $availableVersions = null;

    /**
     * @var array
     */
    protected $loadedVersions = [];

    /**
     * Returns an array with all available versions.
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        if ($this->availableVersions === null) {
            $this->registerAvailableVersions();
        }
        return $this->availableVersions;
    }

    /**
     * If the given version is available, true is returned.
     *
     * @param string $version
     * @return boolean
     */
    public function isVersionAvailable($version)
    {
        if ($this->availableVersions === null) {
            $this->registerAvailableVersions();
        }
        return isset($this->availableVersions[$version]);
    }

    /**
     * Returns the configuration of the given version, if available.
     *
     * @param string $version
     * @return array
     * @throws MigrationException
     */
    public function getMigrationVersion($version)
    {
        if ($this->isVersionAvailable($version)) {
            if ($this->isVersionLoaded($version)) {
                $configuration = $this->loadedVersions[$version];
            } else {
                $configuration = $this->loadConfiguration($version);
                $this->loadedVersions[$version] = $configuration;
            }
            return $configuration;
        }
        throw new MigrationException('Specified version is not available.', 1345821746);
    }

    /**
     * Check if the given version has been loaded already.
     *
     * @param string $version
     * @return boolean
     */
    protected function isVersionLoaded($version)
    {
        return array_key_exists($version, $this->loadedVersions);
    }

    /**
     * Loads a specific version into an array.
     *
     * @param string $version
     * @return array
     */
    abstract protected function loadConfiguration($version);

    /**
     * Loads a list of available versions into an array.
     *
     * @return array
     */
    abstract protected function registerAvailableVersions();
}
