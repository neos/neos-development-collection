<?php
namespace TYPO3\TYPO3CR\Migration\Domain\Repository;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Repository for MigrationStatus instances.
 *
 * @FLOW3\Scope("singleton")
 */
class MigrationStatusRepository extends \TYPO3\FLOW3\Persistence\Repository {

	/**
	 * @var array
	 */
	protected $defaultOrderings = array(
		'workspaceName' => \TYPO3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING,
		'version' => \TYPO3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING
	);

}

?>