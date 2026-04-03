<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamDbId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * A place to cache contentstreamid data, the db id for a content stream is autoincrement and unique there should never be an issue with having this in memory.
 * Todo?
 *
 * @internal you should never need this in userland code
 */
final class ContentStreamDbIdFinder
{
    /**
     * @var array<string, ContentStreamDbId>
     */
    private array $contentStreamIdRuntimeCache = [];

    public function __construct(
        private readonly Connection $dbal,
        private readonly ContentGraphTableNames $tableNames,
    ) {
    }

    public function getContentStreamDbId(ContentStreamId $contentStreamId): ContentStreamDbId
    {
        $contentStreamDbId = $this->getFromRuntimeCache($contentStreamId);
        if ($contentStreamDbId === null) {
            $this->fillRuntimeCacheFromDatabase();
            $contentStreamDbId = $this->getFromRuntimeCache($contentStreamId);
        }

        if ($contentStreamDbId === null) {
            throw new \RuntimeException(sprintf('A ContentStream with id "%s" was not found in the projection, cannot determine ContentStreamDbId.', $contentStreamId->value), 1769945094);
        }

        return $contentStreamDbId;
    }

    private function getFromRuntimeCache(ContentStreamId $contentStreamId): ?ContentStreamDbId
    {
        return $this->contentStreamIdRuntimeCache[$contentStreamId->value] ?? null;
    }

    public function clearRuntimeCacheEntry(ContentStreamId $contentStreamId): void
    {
        unset($this->contentStreamIdRuntimeCache[$contentStreamId->value]);
    }

    private function fillRuntimeCacheFromDatabase(): void
    {
        $allContentStreamIdsStatement = <<<SQL
            SELECT dbId, id FROM {$this->tableNames->contentStream()}
        SQL;
        try {
            $allContentStreamIds = $this->dbal->fetchAllAssociative($allContentStreamIdsStatement);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load content stream ids from database: %s', $e->getMessage()), 1769945050, $e);
        }
        foreach ($allContentStreamIds as $contentStreamIdRow) {
            $this->contentStreamIdRuntimeCache[(string)$contentStreamIdRow['id']] = ContentStreamDbId::fromInt($contentStreamIdRow['dbId']);
        }
    }
}
