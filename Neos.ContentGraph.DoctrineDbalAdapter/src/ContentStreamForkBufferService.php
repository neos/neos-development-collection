<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamDbId;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\ContentStreamForking;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

final readonly class ContentStreamForkBufferService implements ContentRepositoryServiceInterface
{
    use ContentStreamForking;

    public function __construct(
        private ContentGraphTableNames $tableNames,
        private Connection $dbal,
    ) {
    }

    public function countBufferForksByContentStreamId(ContentStreamId $contentStreamId): int
    {
        $countBufferForksStatement = <<<SQL
            SELECT COUNT(*)
            FROM
                {$this->tableNames->contentStream()} as cs
            WHERE
                cs.id IS NULL
                AND cs.sourceContentStreamId = :contentStreamId
        SQL;
        try {
            $row = $this->dbal->fetchOne($countBufferForksStatement, [
                'contentStreamId' => $contentStreamId->value
            ]);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load number of pre forks from database: %s', $e->getMessage()), 1775201750, $e);
        }
        return (int)$row;
    }

    public function preForkContentStreamId(ContentStreamId $contentStreamId, int $numberOfForks): void
    {
        if ($numberOfForks <= 0) {
            throw new \RuntimeException(sprintf('Number of forks must be positive integer %d', $numberOfForks), 1775205430);
        }

        // todo extract into common ContentStream trait?
        $selectContentStreamStatement = <<<SQL
            SELECT dbId, version
                FROM {$this->tableNames->contentStream()}
                WHERE id = :contentStreamId
            LIMIT 1
        SQL;
        try {
            $contentStreamRow = $this->dbal->fetchAssociative($selectContentStreamStatement, [
                'contentStreamId' => $contentStreamId->value
            ]);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('TODO: %s', $e->getMessage()), 1775201750, $e);
        }
        if ($contentStreamRow === false) {
            throw new \RuntimeException(sprintf('content stream %s does not exist', $contentStreamId->value), 1775203449);
        }

        $selectBufferedDbIdsStatement = <<<SQL
            SELECT dbId
                FROM {$this->tableNames->contentStream()}
                WHERE id IS NULL
                AND sourceContentStreamId = :contentStreamId 
        SQL;
        try {
            $existingDbIds = $this->dbal->fetchFirstColumn($selectBufferedDbIdsStatement, [
                'contentStreamId' => $contentStreamId->value,
            ]);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('TODO: %s', $e->getMessage()), 1775201750, $e);
        }

        for ($i = 0; $i < $numberOfForks; $i++) {
            $this->dbal->insert($this->tableNames->contentStream(), [
                'id' => null,
                'version' => 0,
                'sourceContentStreamId' => $contentStreamId->value,
                'sourceContentStreamVersion' => $contentStreamRow['version'],
                'closed' => 0,
                'hasChanges' => 0,
            ]);
        }

        try {
            $updatedDbIds = $this->dbal->fetchFirstColumn($selectBufferedDbIdsStatement, [
                'contentStreamId' => $contentStreamId->value,
            ]);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('TODO: %s', $e->getMessage()), 1775201750, $e);
        }

        $newDbIds = array_diff($updatedDbIds, $existingDbIds);
        if (count($newDbIds) !== $numberOfForks) {
            throw new \RuntimeException(sprintf('Fatal did not create %d new forks only %d', $numberOfForks, count($newDbIds)), 1775205377);
        }

        // TODO Totally unsafe for parallel as we dont use transactions?
        foreach ($newDbIds as $dbId) {
            $this->copyHierarchyRelations(ContentStreamDbId::fromInt($dbId), ContentStreamDbId::fromInt($contentStreamRow['dbId']));
        }
    }
}
