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
use TYPO3\Neos\EventLog\Domain\Model\NodeEvent;

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
     * Find all events which are "top-level" and in a given workspace (or are not NodeEvents)
     *
     * @param integer $offset
     * @param integer $limit
     * @param string $workspaceName
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     * @throws \TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException
     */
    public function findRelevantEventsByWorkspace($offset, $limit, $workspaceName)
    {
        $query = $this->prepareRelevantEventsQuery();
        $query->getQueryBuilder()->select('DISTINCT e');
        $query->getQueryBuilder()
            ->andWhere('e NOT INSTANCE OF ' . NodeEvent::class . ' OR e IN (SELECT nodeevent.uid FROM ' . NodeEvent::class . ' nodeevent WHERE nodeevent.workspaceName = :workspaceName AND nodeevent.parentEvent IS NULL)')
            ->setParameter('workspaceName', $workspaceName);
        $query->getQueryBuilder()->setFirstResult($offset);
        $query->getQueryBuilder()->setMaxResults($limit);

        return $query->execute();
    }

    /**
     * Find all events which are "top-level", i.e. do not have a parent event.
     *
     * @param integer $offset
     * @param integer $limit
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     * @throws \TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException
     */
    public function findRelevantEvents($offset, $limit)
    {
        $query = $this->prepareRelevantEventsQuery();

        $query->getQueryBuilder()->setFirstResult($offset);
        $query->getQueryBuilder()->setMaxResults($limit);

        return $query->execute();
    }

    /**
     * @return \TYPO3\Flow\Persistence\Doctrine\Query
     */
    protected function prepareRelevantEventsQuery()
    {
        $query = $this->createQuery();
        $queryBuilder = $query->getQueryBuilder();

        $queryBuilder->andWhere(
            $queryBuilder->expr()->isNull('e.parentEvent')
        );

        $queryBuilder->orderBy('e.uid', 'DESC');

        return $query;
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
