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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * Finder for changes
 * @Flow\Scope("singleton")
 *
 * @api
 */
final class NodeHiddenStateFinder
{
    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    public function findHiddenState(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): NodeHiddenState {
        $connection = $this->client->getConnection();
        $result = $connection->executeQuery(
            '
                SELECT * FROM neos_contentrepository_projection_nodehiddenstate
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
            return NodeHiddenState::fromDatabaseRow($result);
        } else {
            return NodeHiddenState::noRestrictions();
        }
    }
}
