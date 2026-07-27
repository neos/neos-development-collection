<?php

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\PendingChangesProjection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * Read model for pending changes
 *
 * @internal Only for consumption inside Neos. Not public api because the implementation will be refactored sooner or later: https://github.com/neos/neos-development-collection/issues/5493
 * @Flow\Proxy(false)
 */
final class Change
{
    public const AGGREGATE_DIMENSIONSPACEPOINT_HASH_PLACEHOLDER = 'AGGREGATE';

    public function __construct(
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        // null for aggregate scoped changes (e.g. NodeAggregateNameWasChanged, NodeAggregateTypeWasChanged)
        public ?OriginDimensionSpacePoint $originDimensionSpacePoint,
        public bool $created,
        public bool $changed,
        public bool $moved,
        public bool $deleted,
    ) {
    }

    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection, string $tableName): void
    {
        try {
            $qi = $databaseConnection->quoteIdentifier(...);
            $databaseConnection->insert($tableName, [
                $qi('contentStreamId') => $this->contentStreamId->value,
                $qi('nodeAggregateId') => $this->nodeAggregateId->value,
                $qi('originDimensionSpacePoint') => $this->originDimensionSpacePoint?->toJson(),
                $qi('originDimensionSpacePointHash') => $this->originDimensionSpacePoint?->hash ?: self::AGGREGATE_DIMENSIONSPACEPOINT_HASH_PLACEHOLDER,
                'created' => (int)$this->created,
                'changed' => (int)$this->changed,
                'moved' => (int)$this->moved,
                'deleted' => (int)$this->deleted,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to insert Change to database: %s', $e->getMessage()), 1727272723, $e);
        }
    }

    public function updateToDatabase(Connection $databaseConnection, string $tableName): void
    {
        try {
            $qi = $databaseConnection->quoteIdentifier(...);
            $databaseConnection->update(
                $tableName,
                [
                    'created' => (int)$this->created,
                    'changed' => (int)$this->changed,
                    'moved' => (int)$this->moved,
                    'deleted' => (int)$this->deleted,
                ],
                [
                    $qi('contentStreamId') => $this->contentStreamId->value,
                    $qi('nodeAggregateId') => $this->nodeAggregateId->value,
                    $qi('originDimensionSpacePointHash') => $this->originDimensionSpacePoint?->hash ?: self::AGGREGATE_DIMENSIONSPACEPOINT_HASH_PLACEHOLDER,
                ]
            );
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to update Change in database: %s', $e->getMessage()), 1727272761, $e);
        }
    }

    /**
     * @param array<string,mixed> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            ContentStreamId::fromString(self::binaryToString($databaseRow['contentStreamId'])),
            NodeAggregateId::fromString(self::binaryToString($databaseRow['nodeAggregateId'])),
            isset($databaseRow['originDimensionSpacePoint'])
                ? OriginDimensionSpacePoint::fromJsonString(self::binaryToString($databaseRow['originDimensionSpacePoint']))
                : null,
            (bool)$databaseRow['created'],
            (bool)$databaseRow['changed'],
            (bool)$databaseRow['moved'],
            (bool)$databaseRow['deleted'],
        );
    }

    /**
     * PostgreSQL returns bytea columns as stream resources, while MariaDB/MySQL returns strings.
     */
    private static function binaryToString(mixed $value): string
    {
        if (is_resource($value)) {
            $contents = stream_get_contents($value);
            if ($contents === false) {
                throw new \RuntimeException('Failed to read stream resource for binary database column', 1740000001);
            }
            return $contents;
        }
        return (string)$value;
    }
}
