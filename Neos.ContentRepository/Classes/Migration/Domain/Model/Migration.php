<?php
namespace Neos\ContentRepository\Migration\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * Migration.
 *
 */
class Migration
{
    /**
     * Version that was migrated to.
     *
     * @var string
     */
    protected $version;

    /**
     * @var MigrationConfiguration
     */
    protected $upConfiguration;

    /**
     * @var MigrationConfiguration
     */
    protected $downConfiguration;

    /**
     * @param string $version
     * @param array $configuration
     */
    public function __construct($version, array $configuration)
    {
        $this->version = $version;
        $this->upConfiguration = new MigrationConfiguration($configuration[MigrationStatus::DIRECTION_UP]);
        $this->downConfiguration = new MigrationConfiguration($configuration[MigrationStatus::DIRECTION_DOWN]);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return MigrationConfiguration
     */
    public function getDownConfiguration()
    {
        return $this->downConfiguration;
    }

    /**
     * @return MigrationConfiguration
     */
    public function getUpConfiguration()
    {
        return $this->upConfiguration;
    }
}
