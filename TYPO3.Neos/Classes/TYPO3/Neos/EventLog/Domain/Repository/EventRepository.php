<?php
namespace TYPO3\Neos\EventLog\Domain\Repository;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Repository;
use TYPO3\Flow\Persistence\QueryInterface;

/**
 * The repository for events
 *
 * @Flow\Scope("singleton")
 */
class EventRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'uid' => QueryInterface::ORDER_ASCENDING
    );

    /**
     * Find all events which are "top-level", i.e. do not have a parent event.
     * @param integer $offset
     * @param integer $limit
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     * @throws \TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException
     */
    public function findRelevantEvents($offset, $limit)
    {
        $query = $this->createQuery();
        $queryBuilder = $query->getQueryBuilder();

        $queryBuilder->andWhere(
            $queryBuilder->expr()->isNull('e.parentEvent')
        );

        $queryBuilder->orderBy('e.uid', 'DESC');

        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        return $query->execute();
    }

    /**
     * Remove all events without checking foreign keys. Needed for clearing the table during tests.
     *
     * @return void
     */
    public function removeAll()
    {
        $classMetaData = $this->entityManager->getClassMetadata($this->getEntityClassName());
        $connection = $this->entityManager->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        $truncateTableQuery = $databasePlatform->getTruncateTableSql($classMetaData->getTableName());
        $connection->executeUpdate($truncateTableQuery);
    }
}
