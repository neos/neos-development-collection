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
use Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\EventSourcing\Projection\ProjectorInterface;

/**
 * TODO: this class needs proper testing
 * @internal
 */
class NodeHiddenStateProjector implements ProjectorInterface
{
    public function __construct(
        private readonly DbalClientInterface $client,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->getDatabaseConnection()
                ->executeQuery('SELECT count(*) FROM neos_contentrepository_projection_nodehiddenstate')
                ->fetchColumn() == 0;
    }

    public function reset(): void
    {
        $this->getDatabaseConnection()
            ->executeStatement('TRUNCATE table neos_contentrepository_projection_nodehiddenstate');
    }

    public function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        $this->transactional(function () use ($event) {
            foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
                $nodeHiddenState = new NodeHiddenState(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $dimensionSpacePoint,
                    true
                );
                $nodeHiddenState->addToDatabase($this->getDatabaseConnection());
            }
        });
    }

    public function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        $this->getDatabaseConnection()->executeQuery(
            '
                DELETE FROM
                    neos_contentrepository_projection_nodehiddenstate
                WHERE
                    contentstreamidentifier = :contentStreamIdentifier
                    AND nodeaggregateidentifier = :nodeAggregateIdentifier
                    AND dimensionspacepointhash IN (:dimensionSpacePointHashes)
            ',
            [
                'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                'nodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                'dimensionSpacePointHashes' => $event->affectedDimensionSpacePoints->getPointHashes()
            ],
            [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

    public function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeUpdate('
                INSERT INTO neos_contentrepository_projection_nodehiddenstate (
                    contentstreamidentifier,
                    nodeaggregateidentifier,
                    dimensionspacepoint,
                    dimensionspacepointhash,
                    hidden
                )
                SELECT
                  "' . (string)$event->getContentStreamIdentifier() . '" AS contentstreamidentifier,
                  nodeaggregateidentifier,
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  hidden
                FROM
                    neos_contentrepository_projection_nodehiddenstate h
                    WHERE h.contentstreamidentifier = :sourceContentStreamIdentifier
            ', [
                'sourceContentStreamIdentifier' => (string)$event->getSourceContentStreamIdentifier()
            ]);
        });
    }

    public function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE neos_contentrepository_projection_nodehiddenstate nhs
                    SET
                        nhs.dimensionspacepoint = :newDimensionSpacePoint,
                        nhs.dimensionspacepointhash = :newDimensionSpacePointHash
                    WHERE
                      nhs.dimensionspacepointhash = :originalDimensionSpacePointHash
                      AND nhs.contentstreamidentifier = :contentStreamIdentifier
                      ',
                [
                    'originalDimensionSpacePointHash' => $event->getSource()->hash,
                    'newDimensionSpacePointHash' => $event->getTarget()->hash,
                    'newDimensionSpacePoint' => json_encode($event->getTarget()->jsonSerialize()),
                    'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
                ]
            );
        });
    }

    protected function transactional(\Closure $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}
