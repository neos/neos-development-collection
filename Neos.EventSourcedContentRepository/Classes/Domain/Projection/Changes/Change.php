<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\Changes;

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;

/**
 * Change Read Model
 */
class Change
{
    /**
     * @var ContentStreamIdentifier
     */
    public $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
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
    public ?NodeAggregateIdentifier $removalAttachmentPoint;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        bool $changed,
        bool $moved,
        bool $deleted,
        ?NodeAggregateIdentifier $removalAttachmentPoint = null
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
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert('neos_contentrepository_projection_change', [
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => (string)$this->nodeAggregateIdentifier,
            'originDimensionSpacePoint' => json_encode($this->originDimensionSpacePoint),
            'originDimensionSpacePointHash' => $this->originDimensionSpacePoint->getHash(),
            'changed' => (int)$this->changed,
            'moved' => (int)$this->moved,
            'deleted' => (int)$this->deleted,
            'removalAttachmentPoint' => $this->removalAttachmentPoint !== null ? (string)$this->removalAttachmentPoint : null
        ]);
    }

    public function updateToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->update(
            'neos_contentrepository_projection_change',
            [
            'changed' => (int)$this->changed,
            'moved' => (int)$this->moved,
            'deleted' => (int)$this->deleted,
            'removalAttachmentPoint' => $this->removalAttachmentPoint !== null ? (string)$this->removalAttachmentPoint : null
        ],
            [
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => (string)$this->nodeAggregateIdentifier,
            'originDimensionSpacePoint' => json_encode($this->originDimensionSpacePoint),
            'originDimensionSpacePointHash' => $this->originDimensionSpacePoint->getHash(),
        ]
        );
    }

    /**
     * @param array $databaseRow
     * @return static
     */
    public static function fromDatabaseRow(array $databaseRow)
    {
        return new static(
            ContentStreamIdentifier::fromString($databaseRow['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($databaseRow['nodeAggregateIdentifier']),
            new OriginDimensionSpacePoint(json_decode($databaseRow['originDimensionSpacePoint'], true)),
            (bool)$databaseRow['changed'],
            (bool)$databaseRow['moved'],
            (bool)$databaseRow['deleted'],
            isset($databaseRow['removalAttachmentPoint']) ? NodeAggregateIdentifier::fromString($databaseRow['removalAttachmentPoint']) : null
        );
    }
}
