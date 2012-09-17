<?php
namespace TYPO3\TYPO3CR\Migration\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Migration status to keep track of applied migrations.
 *
 * @FLOW3\ValueObject
 */
class MigrationStatus {

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
	 * Workspace name the migration was applied to.
	 *
	 * @var string
	 */
	protected $workspaceName;

	/**
	 * Direction of this migration status, one of the DIRECTION_* constants.
	 * As TYPO3CR migrations might not be reversible a down migration is just added as new status on top unlike
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
	 * @param string $workspaceName
	 * @param string $direction, DIRECTION_UP or DIRECTION_DOWN
	 * @param \DateTime $applicationTimeStamp
	 */
	public function __construct($version, $workspaceName, $direction, $applicationTimeStamp) {
		$this->version = $version;
		$this->workspaceName = $workspaceName;
		$this->direction = $direction;
		$this->applicationTimeStamp = $applicationTimeStamp;
	}

	/**
	 * The date and time the recorded migration was applied.
	 *
	 * @return \DateTime
	 */
	public function getApplicationTimeStamp() {
		return $this->applicationTimeStamp;
	}

	/**
	 * The direction of the applied migration.
	 *
	 * @return string
	 */
	public function getDirection() {
		return $this->direction;
	}

	/**
	 * The version of the applied migration.
	 *
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * The workspace the migration was applied to.
	 *
	 * @return string
	 */
	public function getWorkspaceName() {
		return $this->workspaceName;
	}
}
?>