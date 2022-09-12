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

namespace Neos\ContentRepository\Core\Projection\NodeHiddenState;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * Node Hidden State database record
 *
 * This model can be used to answer the question if a certain node has the "hidden" flag set or not.
 *
 * It can NOT answer the question whether a Node is hidden because some node above it has been hidden - for that,
 * use the Content Subgraph.
 *
 * @internal
 */
class NodeHiddenStateRecord
{
    private ?ContentStreamId $contentStreamId;

    private ?NodeAggregateId $nodeAggregateId;

    private ?DimensionSpacePoint $dimensionSpacePoint;

    private bool $hidden;

    public function __construct(
        ?ContentStreamId $contentStreamId,
        ?NodeAggregateId $nodeAggregateId,
        ?DimensionSpacePoint $dimensionSpacePoint,
        bool $hidden
    ) {
        $this->contentStreamId = $contentStreamId;
        $this->nodeAggregateId = $nodeAggregateId;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->hidden = $hidden;
    }

    public function addToDatabase(Connection $databaseConnection, string $tableName): void
    {
        if (is_null($this->contentStreamId)) {
            throw new \BadMethodCallException(
                'Cannot add NodeHiddenState to database without a contentStreamId',
                1645383933
            );
        }
        if (is_null($this->nodeAggregateId)) {
            throw new \BadMethodCallException(
                'Cannot add NodeHiddenState to database without a nodeAggregateId',
                1645383950
            );
        }
        if (is_null($this->dimensionSpacePoint)) {
            throw new \BadMethodCallException(
                'Cannot add NodeHiddenState to database without a dimensionSpacePoint',
                1645383962
            );
        }
        $databaseConnection->insert($tableName, [
            'contentStreamId' => (string)$this->contentStreamId,
            'nodeAggregateId' => (string)$this->nodeAggregateId,
            'dimensionSpacePoint' => json_encode($this->dimensionSpacePoint),
            'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash,
            'hidden' => (int)$this->hidden,
        ]);
    }
}
