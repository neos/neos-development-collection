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
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * Read model for pending changes
 *
 * @internal !!! Still a bit unstable - might change in the future.
 * @Flow\Proxy(false)
 */
final class Change
{
    /**
     * @param NodeAggregateId|null $removalAttachmentPoint {@see RemoveNodeAggregate::$removalAttachmentPoint} for docs
     */
    public function __construct(
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        // null for aggregate scoped changes (e.g. NodeAggregateNameWasChanged, NodeAggregateTypeWasChanged)
        public ?OriginDimensionSpacePoint $originDimensionSpacePoint,
        public bool $created,
        public bool $changed,
        public bool $moved,
        public bool $deleted,
        public ?NodeAggregateId $removalAttachmentPoint = null
    ) {
    }


    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection, string $tableName): void
    {
        try {
            $databaseConnection->insert($tableName, [
                'contentStreamId' => $this->contentStreamId->value,
                'nodeAggregateId' => $this->nodeAggregateId->value,
                'originDimensionSpacePoint' => $this->originDimensionSpacePoint?->toJson(),
                'originDimensionSpacePointHash' => $this->originDimensionSpacePoint?->hash ?: 'AGGREGATE',
                'created' => (int)$this->created,
                'changed' => (int)$this->changed,
                'moved' => (int)$this->moved,
                'deleted' => (int)$this->deleted,
                'removalAttachmentPoint' => $this->removalAttachmentPoint?->value
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to insert Change to database: %s', $e->getMessage()), 1727272723, $e);
        }
    }

    public function updateToDatabase(Connection $databaseConnection, string $tableName): void
    {
        try {
            $databaseConnection->update(
                $tableName,
                [
                    'created' => (int)$this->created,
                    'changed' => (int)$this->changed,
                    'moved' => (int)$this->moved,
                    'deleted' => (int)$this->deleted,
                    'removalAttachmentPoint' => $this->removalAttachmentPoint?->value
                ],
                [
                    'contentStreamId' => $this->contentStreamId->value,
                    'nodeAggregateId' => $this->nodeAggregateId->value,
                    'originDimensionSpacePoint' => $this->originDimensionSpacePoint?->toJson(),
                    'originDimensionSpacePointHash' => $this->originDimensionSpacePoint?->hash ?: 'AGGREGATE',
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
            ContentStreamId::fromString($databaseRow['contentStreamId']),
            NodeAggregateId::fromString($databaseRow['nodeAggregateId']),
            $databaseRow['originDimensionSpacePoint'] ?? null
                ? OriginDimensionSpacePoint::fromJsonString($databaseRow['originDimensionSpacePoint'])
                : null,
            (bool)$databaseRow['created'],
            (bool)$databaseRow['changed'],
            (bool)$databaseRow['moved'],
            (bool)$databaseRow['deleted'],
            isset($databaseRow['removalAttachmentPoint'])
                ? NodeAggregateId::fromString($databaseRow['removalAttachmentPoint'])
                : null
        );
    }
}
