<?php
namespace Neos\Neos\EventLog\Domain\Repository;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Repository;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Utility\Exception\PropertyNotAccessibleException;
use Neos\Neos\EventLog\Domain\Model\NodeEvent;

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
    protected $defaultOrderings = [
        'uid' => QueryInterface::ORDER_ASCENDING
    ];

    /**
     * Find all events which are "top-level" and in a given workspace (or are not NodeEvents)
     *
     * @param integer $offset
     * @param integer $limit
     * @param string $workspaceName
     * @return QueryResultInterface
     * @throws PropertyNotAccessibleException
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
     * @return QueryResultInterface
     * @throws PropertyNotAccessibleException
     */
    public function findRelevantEvents($offset, $limit)
    {
        $query = $this->prepareRelevantEventsQuery();

        $query->getQueryBuilder()->setFirstResult($offset);
        $query->getQueryBuilder()->setMaxResults($limit);

        return $query->execute();
    }

    /**
     * @return \Neos\Flow\Persistence\Doctrine\Query
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
    public function removeAll(): void
    {
        $classMetaData = $this->entityManager->getClassMetadata($this->getEntityClassName());
        $connection = $this->entityManager->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        $truncateTableQuery = $databasePlatform->getTruncateTableSql($classMetaData->getTableName());
        $connection->executeUpdate($truncateTableQuery);
    }
}
