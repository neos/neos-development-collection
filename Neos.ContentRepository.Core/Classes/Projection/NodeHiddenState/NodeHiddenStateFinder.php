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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * Finder for hidden states
 *
 * @api
 */
final class NodeHiddenStateFinder implements ProjectionStateInterface
{
    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly string $tableName
    ) {
    }

    public function findHiddenState(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): NodeHiddenState {
        $connection = $this->client->getConnection();
        $result = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE contentstreamid = :contentStreamId
                AND dimensionspacepointhash = :dimensionSpacePointHash
                AND nodeaggregateid = :nodeAggregateId
            ',
            [
                'contentStreamId' => (string)$contentStreamId,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateId' => (string)$nodeAggregateId,
            ]
        )->fetch();

        if (is_array($result)) {
            return new NodeHiddenState(true);
        } else {
            return new NodeHiddenState(false);
        }
    }
}
