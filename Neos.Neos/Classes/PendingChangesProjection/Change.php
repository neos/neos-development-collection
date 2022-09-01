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

use Neos\Flow\Annotations as Flow;
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

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
    public $contentStreamIdentifier;

    /**
     * @var NodeAggregateId
     */
    public $nodeAggregateIdentifier;

    /**
     * @var OriginDimensionSpacePoint
     */
    public $originDimensionSpacePoint;

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
        ContentStreamId $contentStreamIdentifier,
        NodeAggregateId $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        bool $changed,
        bool $moved,
        bool $deleted,
        ?NodeAggregateId $removalAttachmentPoint = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
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
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => (string)$this->nodeAggregateIdentifier,
            'originDimensionSpacePoint' => json_encode($this->originDimensionSpacePoint),
            'originDimensionSpacePointHash' => $this->originDimensionSpacePoint->hash,
            'changed' => (int)$this->changed,
            'moved' => (int)$this->moved,
            'deleted' => (int)$this->deleted,
            'removalAttachmentPoint' => $this->removalAttachmentPoint?->__toString()
        ]);
    }

    public function updateToDatabase(Connection $databaseConnection, string $tableName): void
    {
        $databaseConnection->update(
            $tableName,
            [
                'changed' => (int)$this->changed,
                'moved' => (int)$this->moved,
                'deleted' => (int)$this->deleted,
                'removalAttachmentPoint' => $this->removalAttachmentPoint?->__toString()
            ],
            [
                'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
                'nodeAggregateIdentifier' => (string)$this->nodeAggregateIdentifier,
                'originDimensionSpacePoint' => json_encode($this->originDimensionSpacePoint),
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
            ContentStreamId::fromString($databaseRow['contentStreamIdentifier']),
            NodeAggregateId::fromString($databaseRow['nodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromJsonString($databaseRow['originDimensionSpacePoint']),
            (bool)$databaseRow['changed'],
            (bool)$databaseRow['moved'],
            (bool)$databaseRow['deleted'],
            isset($databaseRow['removalAttachmentPoint'])
                ? NodeAggregateId::fromString($databaseRow['removalAttachmentPoint'])
                : null
        );
    }
}
