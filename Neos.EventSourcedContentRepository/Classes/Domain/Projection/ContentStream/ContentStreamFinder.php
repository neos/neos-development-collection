<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Projection\ContentStream;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class ContentStreamFinder
{
    const STATE_CREATED = 'CREATED';
    const STATE_IN_USE_BY_WORKSPACE = 'IN_USE_BY_WORKSPACE';
    const STATE_REBASING = 'REBASING';
    const STATE_REBASE_ERROR = 'REBASE_ERROR';
    const STATE_NO_LONGER_IN_USE = 'NO_LONGER_IN_USE';

    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     *
     * @param WorkspaceName $name
     * @return Workspace|null
     */
    public function findUnusedContentStreams(): array
    {
        $connection = $this->client->getConnection();
        $databaseRows = $connection->executeQuery(
            '
            SELECT contentStreamIdentifier FROM neos_contentrepository_projection_contentstream_v1
                WHERE removed = FALSE
                AND state IN (:state)
            ',
            [
                'state' => [
                    self::STATE_NO_LONGER_IN_USE,
                    self::STATE_REBASE_ERROR,
                ]
            ],
            [
                'state' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAll();

        $contentStreams = [];
        foreach ($databaseRows as $databaseRow) {
            $contentStreams[] = ContentStreamIdentifier::fromString($databaseRow['contentStreamIdentifier']);
        }

        return $contentStreams;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return string one of the self::STATE_* constants
     */
    public function findStateForContentStream(ContentStreamIdentifier $contentStreamIdentifier): ?string
    {
        $connection = $this->client->getConnection();
        /* @var $state string|false */
        $state = $connection->executeQuery(
            '
            SELECT state FROM neos_contentrepository_projection_contentstream_v1
                WHERE contentStreamIdentifier = :contentStreamIdentifier
            ',
            [
                'contentStreamIdentifier' => $contentStreamIdentifier->jsonSerialize()
            ]
        )->fetchColumn();

        if ($state === false) {
            return null;
        }

        return $state;
    }
}
