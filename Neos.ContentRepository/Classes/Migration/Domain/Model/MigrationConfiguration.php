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
 * Migration configuration for a specific direction.
 */
class MigrationConfiguration
{
    /**
     * @var string
     */
    protected $comments;

    /**
     * @var string
     */
    protected $warnings;

    /**
     * @var array
     */
    protected $migration;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->comments = isset($configuration['comments']) ? $configuration['comments'] : null;
        $this->warnings = isset($configuration['warnings']) ? $configuration['warnings'] : null;
        $this->migration = isset($configuration['migration']) ? $configuration['migration'] : null;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @return boolean
     */
    public function hasComments()
    {
        return ($this->comments !== null);
    }

    /**
     * @return array
     */
    public function getMigration()
    {
        return $this->migration;
    }

    /**
     * @return string
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @return boolean
     */
    public function hasWarnings()
    {
        return ($this->warnings !== null);
    }
}
