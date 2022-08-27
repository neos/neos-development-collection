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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\ProjectionStateInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;

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
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): NodeHiddenState {
        $connection = $this->client->getConnection();
        $result = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE contentstreamidentifier = :contentStreamIdentifier
                AND dimensionspacepointhash = :dimensionSpacePointHash
                AND nodeaggregateidentifier = :nodeAggregateIdentifier
            ',
            [
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            ]
        )->fetch();

        if (is_array($result)) {
            return new NodeHiddenState(true);
        } else {
            return new NodeHiddenState(false);
        }
    }
}
