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
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal you should never need this in userland code
 */
final class ContentStreamLayerFinder
{
    public function __construct(
        private readonly Connection $dbal,
        private readonly ContentGraphTableNames $tableNames,
    ) {
    }

    public function getContentStreamLayers(ContentStreamId $contentStreamId): ContentStreamLayers
    {
        $contentStreamLayersStatement = <<<SQL
            SELECT contentstreamlayer FROM {$this->tableNames->contentStreamLayer()}
                WHERE contentstreamid = :contentStreamId
        SQL;
        try {
            $contentStreamLayers = $this->dbal->fetchFirstColumn($contentStreamLayersStatement, ['contentStreamId' => $contentStreamId->value]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load content stream ids from database: %s', $e->getMessage()), 1769945050, $e);
        }
        if ($contentStreamLayers === []) {
            throw new \RuntimeException(sprintf('Content stream "%s" does not exist. No layers found.', $contentStreamId->value), 1782030038);
        }
        return ContentStreamLayers::fromArray($contentStreamLayers);
    }
}
