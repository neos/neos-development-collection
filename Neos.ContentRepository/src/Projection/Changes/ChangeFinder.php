<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection\Changes;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\Changes\Change;
use Neos\ContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * Finder for changes
 * @Flow\Scope("singleton")
 *
 * @api
 */
final class ChangeFinder
{
    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return array|Change[]
     */
    public function findByContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): array
    {
        $connection = $this->client->getConnection();
        $changeRows = $connection->executeQuery(
            '
                SELECT * FROM neos_contentrepository_projection_change
                WHERE contentStreamIdentifier = :contentStreamIdentifier
            ',
            [
                ':contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetchAll();
        $changes = [];
        foreach ($changeRows as $changeRow) {
            $changes[] = Change::fromDatabaseRow($changeRow);
        }
        return $changes;
    }

    public function countByContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): int
    {
        $connection = $this->client->getConnection();
        return (int)$connection->executeQuery(
            '
                SELECT * FROM neos_contentrepository_projection_change
                WHERE contentStreamIdentifier = :contentStreamIdentifier
            ',
            [
                ':contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->rowCount();
    }
}
