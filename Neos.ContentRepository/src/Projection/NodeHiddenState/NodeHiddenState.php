<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Projection\NodeHiddenState;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

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
    private ?ContentStreamIdentifier $contentStreamIdentifier;

    private ?NodeAggregateIdentifier $nodeAggregateIdentifier;

    private ?DimensionSpacePoint $dimensionSpacePoint;

    private bool $hidden;

    public function __construct(
        ?ContentStreamIdentifier $contentStreamIdentifier,
        ?NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?DimensionSpacePoint $dimensionSpacePoint,
        bool $hidden
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->hidden = $hidden;
    }

    public function addToDatabase(Connection $databaseConnection): void
    {
        if (is_null($this->contentStreamIdentifier)) {
            throw new \BadMethodCallException(
                'Cannot add NodeHiddenState to database without a contentStreamIdentifier',
                1645383933
            );
        }
        if (is_null($this->nodeAggregateIdentifier)) {
            throw new \BadMethodCallException(
                'Cannot add NodeHiddenState to database without a nodeAggregateIdentifier',
                1645383950
            );
        }
        if (is_null($this->dimensionSpacePoint)) {
            throw new \BadMethodCallException(
                'Cannot add NodeHiddenState to database without a dimensionSpacePoint',
                1645383962
            );
        }
        $databaseConnection->insert('neos_contentrepository_projection_nodehiddenstate', [
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => (string)$this->nodeAggregateIdentifier,
            'dimensionSpacePoint' => json_encode($this->dimensionSpacePoint),
            'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash,
            'hidden' => (int)$this->hidden,
        ]);
    }

    /**
     * @param array<string,mixed> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            ContentStreamIdentifier::fromString($databaseRow['contentstreamidentifier']),
            NodeAggregateIdentifier::fromString($databaseRow['nodeaggregateidentifier']),
            DimensionSpacePoint::fromJsonString($databaseRow['dimensionspacepoint']),
            (bool)$databaseRow['hidden']
        );
    }

    public static function noRestrictions(): self
    {
        return new self(
            null,
            null,
            null,
            false
        );
    }

    /**
     * @return bool
     * @api
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
