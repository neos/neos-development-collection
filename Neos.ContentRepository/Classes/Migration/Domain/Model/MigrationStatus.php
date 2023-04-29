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

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * Migration status to keep track of applied migrations.
 *
 * @Flow\ValueObject(embedded=false)
 */
class MigrationStatus
{
    /**
     * @var string
     */
    const DIRECTION_UP = 'up';

    /**
     * @var string
     */
    const DIRECTION_DOWN = 'down';

    /**
     * Version that was migrated to.
     *
     * @var string
     * @ORM\Column(length=14)
     */
    protected $version;

    /**
     * Direction of this migration status, one of the DIRECTION_* constants.
     * As ContentRepository migrations might not be reversible a down migration is just added as new status on top unlike
     * persistence migrations.
     *
     * @var string
     * @ORM\Column(length=4)
     */
    protected $direction;

    /**
     * @var \DateTime
     */
    protected $applicationTimeStamp;

    /**
     * @param string $version
     * @param string $direction, DIRECTION_UP or DIRECTION_DOWN
     * @param \DateTime $applicationTimeStamp
     */
    public function __construct($version, $direction, $applicationTimeStamp)
    {
        $this->version = $version;
        $this->direction = $direction;
        $this->applicationTimeStamp = $applicationTimeStamp;
    }

    /**
     * The date and time the recorded migration was applied.
     *
     * @return \DateTime
     */
    public function getApplicationTimeStamp()
    {
        return $this->applicationTimeStamp;
    }

    /**
     * The direction of the applied migration.
     *
     * @return string
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * The version of the applied migration.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
