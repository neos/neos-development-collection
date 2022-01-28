<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\NodeHiddenState;

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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;

/**
 * Node Hidden State Read Model.
 *
 * This model can be used to answer the question if a certain node has the "hidden" flag set or not.
 *
 * It can NOT answer the question whether a Node is hidden because some node above it has been hidden - for that,
 * use the Content Subgraph.
 */
class NodeHiddenState
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * @var boolean
     */
    private $hidden;

    /**
     * NodeHiddenState constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param bool $hidden
     */
    public function __construct(?ContentStreamIdentifier $contentStreamIdentifier, ?NodeAggregateIdentifier $nodeAggregateIdentifier, ?DimensionSpacePoint $dimensionSpacePoint, ?bool $hidden)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->hidden = $hidden;
    }

    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert('neos_contentrepository_projection_nodehiddenstate', [
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => (string)$this->nodeAggregateIdentifier,
            'dimensionSpacePoint' => json_encode($this->dimensionSpacePoint),
            'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash,
            'hidden' => (int)$this->hidden,
        ]);
    }

    /**
     * @param array $databaseRow
     * @return static
     */
    public static function fromDatabaseRow(array $databaseRow)
    {
        return new static(
            ContentStreamIdentifier::fromString($databaseRow['contentstreamidentifier']),
            NodeAggregateIdentifier::fromString($databaseRow['nodeaggregateidentifier']),
            DimensionSpacePoint::fromJsonString($databaseRow['dimensionspacepoint']),
            (bool)$databaseRow['hidden']
        );
    }

    public static function noRestrictions(): NodeHiddenState
    {
        return new static(
            null,
            null,
            null,
            false
        );
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
