<?php
namespace Neos\ContentRepository\Migration\Domain\Factory;

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
use Neos\ContentRepository\Migration\Configuration\ConfigurationInterface;
use Neos\ContentRepository\Migration\Domain\Model\Migration;

/**
 * Migration factory.
 *
 */
class MigrationFactory
{
    /**
     * @Flow\Inject
     * @var ConfigurationInterface
     */
    protected $migrationConfiguration;

    /**
     * @param string $version
     * @return Migration
     */
    public function getMigrationForVersion($version)
    {
        $migrationConfiguration = $this->migrationConfiguration->getMigrationVersion($version);
        $migration = new Migration($version, $migrationConfiguration);
        return $migration;
    }

    /**
     * Return array of all available migrations with the current configuration type
     *
     * @return array
     */
    public function getAvailableMigrationsForCurrentConfigurationType()
    {
        return $this->migrationConfiguration->getAvailableVersions();
    }
}
