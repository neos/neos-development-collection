<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilege;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\Doctrine\ConditionGenerator;

/**
 * A node privilege to restricting reading of nodes.
 * Nodes not granted for reading will be filtered via SQL.
 *
 * Currently only doctrine persistence is supported as we use
 * the doctrine filter api, to rewrite SQL queries.
 */
class ReadNodePrivilege extends EntityPrivilege {

	/**
	 * @param string $entityType
	 * @return boolean
	 */
	public function matchesEntityType($entityType) {
		return $entityType === NodeData::class;
	}

	/**
	 * @return ConditionGenerator
	 */
	protected function getConditionGenerator() {
		return new ConditionGenerator();
	}
}