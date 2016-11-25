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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\Files as Files;
use Neos\ContentRepository\Migration\Configuration\Configuration;
use Neos\ContentRepository\Migration\Exception\MigrationException;

/**
 * Migration Configuration using YAML files.
 */
class YamlConfiguration extends Configuration
{
    /**
     * @Flow\Inject
     * @var YamlSource
     */
    protected $yamlSourceImporter;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * Loads a list of available versions into an array.
     *
     * @return array
     * @throws MigrationException
     */
    protected function registerAvailableVersions()
    {
        $this->availableVersions = array();
        foreach ($this->packageManager->getActivePackages() as $package) {
            $possibleMigrationsPath = Files::concatenatePaths(array(
                $package->getPackagePath(),
                'Migrations/TYPO3CR'
            ));
            if (!is_dir($possibleMigrationsPath)) {
                continue;
            }
            $directoryIterator = new \DirectoryIterator($possibleMigrationsPath);
            foreach ($directoryIterator as $fileInfo) {
                $filename = $fileInfo->getFilename();
                if ($fileInfo->isFile() && $filename[0] !== '.' && (substr($filename, -5) === '.yaml')) {
                    $versionFile = Files::getUnixStylePath($fileInfo->getPathname());
                    $versionNumber = substr(substr($filename, 7), 0, -5);
                    if (array_key_exists($versionNumber, $this->availableVersions)) {
                        throw new MigrationException('The migration version ' . $versionNumber . ' exists twice, that is not supported.', 1345823182);
                    }
                    $this->availableVersions[$versionNumber] = array(
                        'filePathAndName' => $versionFile,
                        'package' => $package,
                        'formattedVersionNumber' =>
                                // DD-MM-YYYY HH:MM:SS
                            $versionNumber[6] . $versionNumber[7] . '-' .
                            $versionNumber[4] . $versionNumber[5] . '-' .
                            $versionNumber[0] . $versionNumber[1] . $versionNumber[2] . $versionNumber[3] . ' ' .
                            $versionNumber[8] . $versionNumber[9] . ':' . $versionNumber[10] . $versionNumber[11] . ':' . $versionNumber[12] . $versionNumber[13]
                    );
                }
            }
        }
        ksort($this->availableVersions);
    }

    /**
     * Loads a specific version into an array.
     *
     * @param string $version
     * @return array
     * @throws MigrationException
     */
    protected function loadConfiguration($version)
    {
        if (!$this->isVersionAvailable($version)) {
            throw new MigrationException('The requested YamlConfiguration was not available.', 1345822283);
        }

        $configuration = $this->yamlSourceImporter->load(substr($this->availableVersions[$version]['filePathAndName'], 0, -5));
        return $configuration;
    }
}
