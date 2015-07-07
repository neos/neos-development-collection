<?php
namespace TYPO3\Neos\EventLog\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Repository;
use TYPO3\Flow\Persistence\QueryInterface;

/**
 * The repository for events
 *
 * @Flow\Scope("singleton")
 */
class EventRepository extends Repository {

	/**
	 * @var array
	 */
	protected $defaultOrderings = array(
		'uid' => QueryInterface::ORDER_ASCENDING
	);

	/**
	 * Find all events which are "top-level", i.e. do not have a parent event.
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 * @throws \TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException
	 */
	public function findRelevantEvents() {
		$query = $this->createQuery();
		$queryBuilder = $query->getQueryBuilder();

		$queryBuilder->andWhere(
			$queryBuilder->expr()->isNull('e.parentEvent')
		);

		$queryBuilder->orderBy('e.uid', 'DESC');

		return $query->execute();
	}

	/**
	 * Remove all events without checking foreign keys. Needed for clearing the table during tests.
	 *
	 * @return void
	 */
	public function removeAll() {
		$classMetaData = $this->entityManager->getClassMetadata($this->getEntityClassName());
		$connection = $this->entityManager->getConnection();
		$databasePlatform = $connection->getDatabasePlatform();
		$connection->query('SET FOREIGN_KEY_CHECKS=0');
		$truncateTableQuery = $databasePlatform->getTruncateTableSql($classMetaData->getTableName());
		$connection->executeUpdate($truncateTableQuery);
		$connection->query('SET FOREIGN_KEY_CHECKS=1');
	}
}