<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection\Changes;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * TODO: this class needs testing and probably a major refactoring!
 *
 * @Flow\Scope("singleton")
 */
class ChangeProjector implements ProjectorInterface
{
    /**
     * @param DbalClientInterface $client
     */
    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly WorkspaceFinder $workspaceFinder
    )
    {
    }

    public function isEmpty(): bool
    {
        return $this->getDatabaseConnection()
                ->executeQuery('SELECT count(*) FROM neos_contentrepository_projection_change')
                ->fetchColumn() == 0;
    }

    public function reset(): void
    {
        $this->getDatabaseConnection()->executeStatement('TRUNCATE table neos_contentrepository_projection_change');
    }


    public function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        // WORKAROUND: we simply use the first MoveNodeMapping here to find the dimension space point
        // @todo properly handle this
        if (is_null($event->getNodeMoveMappings())) {
            throw new \Exception(
                'Could not apply NodeAggregateWasMoved to change projection due to missing nodeMoveMappings.',
                1645382694
            );
        }
        $mapping = iterator_to_array($event->getNodeMoveMappings());

        $this->markAsMoved(
            $event->getContentStreamIdentifier(),
            $event->getNodeAggregateIdentifier(),
            $mapping[0]->getMovedNodeOrigin()
        );
    }

    public function whenNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {
        $this->markAsChanged(
            $event->contentStreamIdentifier,
            $event->nodeAggregateIdentifier,
            $event->originDimensionSpacePoint
        );
    }

    public function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        $this->markAsChanged(
            $event->contentStreamIdentifier,
            $event->nodeAggregateIdentifier,
            $event->originDimensionSpacePoint
        );
    }

    public function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $this->markAsChanged(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
            );
        }
    }

    public function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $this->markAsChanged(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
            );
        }
    }

    // TODO: Node Creation

    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->transactional(function () use ($event) {
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier(
                $event->contentStreamIdentifier
            );
            if ($workspace instanceof Workspace && $workspace->getBaseWorkspaceName() === null) {
                // Workspace is the live workspace (has no base workspace); we do not need to do anything
                return;
            }

            $this->getDatabaseConnection()->executeUpdate(
                'DELETE FROM neos_contentrepository_projection_change
                    WHERE
                        contentStreamIdentifier = :contentStreamIdentifier
                        AND nodeAggregateIdentifier = :nodeAggregateIdentifier
                        AND originDimensionSpacePointHash IN (:affectedDimensionSpacePointHashes)
                    ',
                [
                    'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                    'nodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                    'affectedDimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints
                        ->getPointHashes()
                ],
                [
                    'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
                ]
            );

            foreach ($event->affectedOccupiedDimensionSpacePoints as $occupiedDimensionSpacePoint) {
                $this->getDatabaseConnection()->executeUpdate(
                    'INSERT INTO neos_contentrepository_projection_change
                            (contentStreamIdentifier, nodeAggregateIdentifier, originDimensionSpacePoint,
                             originDimensionSpacePointHash, deleted, changed, moved, removalAttachmentPoint)
                        VALUES (
                            :contentStreamIdentifier,
                            :nodeAggregateIdentifier,
                            :originDimensionSpacePoint,
                            :originDimensionSpacePointHash,
                            1,
                            0,
                            0,
                            :removalAttachmentPoint
                        )
                    ',
                    [
                        'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                        'nodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                        'originDimensionSpacePoint' => json_encode($occupiedDimensionSpacePoint),
                        'originDimensionSpacePointHash' => $occupiedDimensionSpacePoint->hash,
                        'removalAttachmentPoint' => $event->removalAttachmentPoint?->__toString()
                    ]
                );
            }
        });
    }

    public function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE neos_contentrepository_projection_change c
                    SET
                        c.originDimensionSpacePoint = :newDimensionSpacePoint,
                        c.originDimensionSpacePointHash = :newDimensionSpacePointHash
                    WHERE
                      c.originDimensionSpacePointHash = :originalDimensionSpacePointHash
                      AND c.contentStreamIdentifier = :contentStreamIdentifier
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

    protected function markAsChanged(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void {
        $this->transactional(function () use (
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $originDimensionSpacePoint
        ) {
            // HACK: basically we are not allowed to read other Projection's finder methods here;
            // but we nevertheless do it.
            // we can maybe figure out another way of solving this lateron.
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier);
            if ($workspace instanceof Workspace && $workspace->getBaseWorkspaceName() === null) {
                // Workspace is the live workspace (has no base workspace); we do not need to do anything
                return;
            }
            $change = $this->getChange(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $originDimensionSpacePoint
            );
            if ($change === null) {
                $change = new Change(
                    $contentStreamIdentifier,
                    $nodeAggregateIdentifier,
                    $originDimensionSpacePoint,
                    true,
                    false,
                    false
                );
                $change->addToDatabase($this->getDatabaseConnection());
            } else {
                $change->changed = true;
                $change->updateToDatabase($this->getDatabaseConnection());
            }
        });
    }

    protected function markAsMoved(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void {
        $this->transactional(function () use (
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $originDimensionSpacePoint
        ) {
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier);
            if ($workspace instanceof Workspace && $workspace->getBaseWorkspaceName() === null) {
                // Workspace is the live workspace (has no base workspace); we do not need to do anything
                return;
            }
            $change = $this->getChange(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $originDimensionSpacePoint
            );
            if ($change === null) {
                $change = new Change(
                    $contentStreamIdentifier,
                    $nodeAggregateIdentifier,
                    $originDimensionSpacePoint,
                    false,
                    true,
                    false
                );
                $change->addToDatabase($this->getDatabaseConnection());
            } else {
                $change->moved = true;
                $change->updateToDatabase($this->getDatabaseConnection());
            }
        });
    }

    protected function getChange(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?Change {
        $changeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentrepository_projection_change n
WHERE n.contentStreamIdentifier = :contentStreamIdentifier
AND n.nodeAggregateIdentifier = :nodeAggregateIdentifier
AND n.originDimensionSpacePointHash = :originDimensionSpacePointHash',
            [
                'contentStreamIdentifier' => $contentStreamIdentifier,
                'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash
            ]
        )->fetch();

        // We always allow root nodes
        return $changeRow ? Change::fromDatabaseRow($changeRow) : null;
    }

    protected function transactional(\Closure $operations): void
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
