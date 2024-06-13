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
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * Change Read Model
 *
 * !!! Still a bit unstable - might change in the future.
 * @Flow\Proxy(false)
 */
class Change
{
    /**
     * @var ContentStreamId
     */
    public $contentStreamId;

    /**
     * @var NodeAggregateId
     */
    public $nodeAggregateId;

    /**
     * @var OriginDimensionSpacePoint
     */
    public $originDimensionSpacePoint;

    /**
     * @var bool
     */
    public $created;

    /**
     * @var bool
     */
    public $changed;

    /**
     * @var bool
     */
    public $moved;

    /**
     * @var bool
     */
    public $deleted;

    /**
     * {@see RemoveNodeAggregate::$removalAttachmentPoint} for docs
     */
    public ?NodeAggregateId $removalAttachmentPoint;

    public function __construct(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        bool $created,
        bool $changed,
        bool $moved,
        bool $deleted,
        ?NodeAggregateId $removalAttachmentPoint = null
    ) {
        $this->contentStreamId = $contentStreamId;
        $this->nodeAggregateId = $nodeAggregateId;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->created = $created;
        $this->changed = $changed;
        $this->moved = $moved;
        $this->deleted = $deleted;
        $this->removalAttachmentPoint = $removalAttachmentPoint;
    }


    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection, string $tableName): void
    {
        $databaseConnection->insert($tableName, [
            'contentStreamId' => $this->contentStreamId->value,
            'nodeAggregateId' => $this->nodeAggregateId->value,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint->toJson(),
            'originDimensionSpacePointHash' => $this->originDimensionSpacePoint->hash,
            'created' => (int)$this->created,
            'changed' => (int)$this->changed,
            'moved' => (int)$this->moved,
            'deleted' => (int)$this->deleted,
            'removalAttachmentPoint' => $this->removalAttachmentPoint?->value
        ]);
    }

    public function updateToDatabase(Connection $databaseConnection, string $tableName): void
    {
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
                'originDimensionSpacePoint' => $this->originDimensionSpacePoint->toJson(),
                'originDimensionSpacePointHash' => $this->originDimensionSpacePoint->hash,
            ]
        );
    }

    /**
     * @param array<string,mixed> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            ContentStreamId::fromString($databaseRow['contentStreamId']),
            NodeAggregateId::fromString($databaseRow['nodeAggregateId']),
            OriginDimensionSpacePoint::fromJsonString($databaseRow['originDimensionSpacePoint']),
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
