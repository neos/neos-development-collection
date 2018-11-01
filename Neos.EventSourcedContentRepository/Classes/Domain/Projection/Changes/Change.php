<?php
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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;

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
     * @var DimensionSpacePoint
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
     * Change constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $originDimensionSpacePoint
     * @param bool $changed
     * @param bool $moved
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePoint $originDimensionSpacePoint, bool $changed, bool $moved)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->changed = $changed;
        $this->moved = $moved;
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
            'moved' => (int)$this->moved
        ]);
    }

    public function updateToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->update('neos_contentrepository_projection_change', [
            'changed' => (int)$this->changed,
            'moved' => (int)$this->moved
        ],
        [
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => (string)$this->nodeAggregateIdentifier,
            'originDimensionSpacePoint' => json_encode($this->originDimensionSpacePoint),
            'originDimensionSpacePointHash' => $this->originDimensionSpacePoint->getHash(),
        ]);
    }

    /**
     * @param array $databaseRow
     * @return static
     */
    public static function fromDatabaseRow(array $databaseRow)
    {
        return new static(
            new ContentStreamIdentifier($databaseRow['contentStreamIdentifier']),
            new NodeAggregateIdentifier($databaseRow['nodeAggregateIdentifier']),
            new DimensionSpacePoint(json_decode($databaseRow['originDimensionSpacePoint'], true)),
            (bool)$databaseRow['changed'],
            (bool)$databaseRow['moved']
        );
    }
}
