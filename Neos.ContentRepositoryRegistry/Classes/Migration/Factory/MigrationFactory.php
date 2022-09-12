<?php
namespace Neos\ContentRepositoryRegistry\Migration\Factory;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\NodeMigration\Command\MigrationConfiguration;
use Neos\ContentRepositoryRegistry\Migration\Configuration\ConfigurationInterface;
use Neos\Flow\Annotations as Flow;

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

    public function getMigrationForVersion($version): MigrationConfiguration
    {
        return new MigrationConfiguration($this->migrationConfiguration->getMigrationVersion($version));
    }
}
