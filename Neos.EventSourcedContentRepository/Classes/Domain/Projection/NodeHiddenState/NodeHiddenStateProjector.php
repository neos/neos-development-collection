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
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasDisabled;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasEnabled;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NodeHiddenStateProjector implements ProjectorInterface
{
    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    public function isEmpty(): bool
    {
        return $this->getDatabaseConnection()
                ->executeQuery('SELECT count(*) FROM neos_contentrepository_projection_nodehiddenstate')
                ->fetchColumn() == 0;
    }

    public function reset(): void
    {
        $this->transactional(function () {
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentrepository_projection_nodehiddenstate');
        });
    }

    public function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event)
    {
        $this->transactional(function () use ($event) {
            foreach ($event->getAffectedDimensionSpacePoints() as $dimensionSpacePoint) {
                $nodeHiddenState = new NodeHiddenState(
                    $event->getContentStreamIdentifier(),
                    $event->getNodeAggregateIdentifier(),
                    $dimensionSpacePoint,
                    true
                );
                $nodeHiddenState->addToDatabase($this->getDatabaseConnection());
            }
        });
    }

    public function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event)
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
                'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier(),
                'nodeAggregateIdentifier' => (string)$event->getNodeAggregateIdentifier(),
                'dimensionSpacePointHashes' => $event->getAffectedDimensionSpacePoints()->getPointHashes()
            ],
            [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

    public function whenContentStreamWasForked(ContentStreamWasForked $event)
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

    /**
     * @param callable $operations
     */
    protected function transactional(callable $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    /**
     * @return Connection
     */
    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}
