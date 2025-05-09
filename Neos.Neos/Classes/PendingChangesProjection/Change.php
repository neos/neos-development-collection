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
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
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
        private ?NodeAggregateId $removalAttachmentPoint = null
    ) {
    }

    /**
     * Before soft removals the removalAttachmentPoint was metadata reached through from command to the final event.
     *
     * It stored the document node id of the removed node, as that was needed later for the change display and publication.
     *
     * See also https://github.com/neos/neos-development-collection/issues/4487
     *
     * We continue to have {@see RemoveNodeAggregate::$removalAttachmentPoint} and {@see NodeAggregateWasRemoved::$removalAttachmentPoint}
     * in the core to allow publishing and rebasing the legacy removals as in previous betas.
     *
     * @deprecated with Neos 9 Beta 19, obsolete via soft removals. Might be removed at any point.
     */
    public function getLegacyRemovalAttachmentPoint(): ?NodeAggregateId
    {
        return $this->removalAttachmentPoint;
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
                'originDimensionSpacePointHash' => $this->originDimensionSpacePoint?->hash ?: self::AGGREGATE_DIMENSIONSPACEPOINT_HASH_PLACEHOLDER,
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
                    'originDimensionSpacePointHash' => $this->originDimensionSpacePoint?->hash ?: self::AGGREGATE_DIMENSIONSPACEPOINT_HASH_PLACEHOLDER,
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
