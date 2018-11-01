<?php
namespace Neos\EventSourcedContentRepository\Domain\Projection\Changes;

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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodePropertyWasSet;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeWasHidden;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeWasMoved;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeWasShown;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasRebased;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\Workspace;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class ChangeProjector implements ProjectorInterface
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
                ->executeQuery('SELECT count(*) FROM neos_contentrepository_projection_change')
                ->fetchColumn() == 0;
    }

    public function reset(): void
    {
        $this->transactional(function () {
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentrepository_projection_change');
        });
    }

    public function whenNodePropertyWasSet(NodePropertyWasSet $event)
    {
        $this->markAsChanged($event->getContentStreamIdentifier(), $event->getNodeAggregateIdentifier(), $event->getOriginDimensionSpacePoint());
    }

    // TODO fix (change from NodeAggregateIdentifier to NodeIdentifier
    /*public function whenNodeWasHidden(NodeWasHidden $event)
    {
        $this->markAsChanged($event->getContentStreamIdentifier(), $event->getNodeIdentifier());
    }

    public function whenNodeWasShown(NodeWasShown $event)
    {
        $this->markAsChanged($event->getContentStreamIdentifier(), $event->getNodeIdentifier());
    }

    public function whenNodeWasMoved(NodeWasMoved $event)
    {
        $this->markAsMoved($event->getContentStreamIdentifier(), $event->getNodeIdentifier());
    }*/

    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event)
    {
        $this->transactional(function () use ($event) {
            $workspaces = $this->workspaceFinder->findAll();
            $workspaceContentStreamIdentifiers = [];
            foreach ($workspaces as $workspace) {
                if ($workspace->getBaseWorkspaceName() !== null) {
                    $workspaceContentStreamIdentifiers[] = $workspace->getCurrentContentStreamIdentifier();
                }
            }

            $this->getDatabaseConnection()->executeQuery('
                DELETE FROM neos_contentrepository_projection_change
WHERE contentStreamIdentifier NOT IN (:contentStreamIdentifier)',
                [
                    ':contentStreamIdentifier' => $workspaceContentStreamIdentifiers
                ],
                [
                    ':contentStreamIdentifier' => Connection::PARAM_STR_ARRAY
                ]);

            $workspace = $this->workspaceFinder->findOneByName($event->getWorkspaceName());
            if ($workspace instanceof Workspace) {
                $this->getDatabaseConnection()->delete('neos_contentrepository_projection_change', [
                    'contentStreamIdentifier' => (string)$workspace->getCurrentContentStreamIdentifier()
                ]);
            }
        });
    }

    protected function markAsChanged(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePoint $originDimensionSpacePoint)
    {
        $this->transactional(function () use ($contentStreamIdentifier, $nodeAggregateIdentifier, $originDimensionSpacePoint) {
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier);
            if ($workspace instanceof Workspace && $workspace->getBaseWorkspaceName() !== null) {
                $change = $this->getChange($contentStreamIdentifier, $nodeAggregateIdentifier, $originDimensionSpacePoint);
                if ($change === null) {
                    $change = new Change(
                        $contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $originDimensionSpacePoint,

                        true,
                        false
                    );
                    $change->addToDatabase($this->getDatabaseConnection());
                } else {
                    $change->changed = true;
                    $change->updateToDatabase($this->getDatabaseConnection());
                }
            }
        });
    }

    protected function markAsMoved(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePoint $originDimensionSpacePoint)
    {
        $this->transactional(function () use ($contentStreamIdentifier, $nodeAggregateIdentifier, $originDimensionSpacePoint) {
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier);
            if ($workspace instanceof Workspace && $workspace->getBaseWorkspaceName() !== null) {
                $change = $this->getChange($contentStreamIdentifier, $nodeAggregateIdentifier, $originDimensionSpacePoint);
                if ($change === null) {
                    $change = new Change(
                        $contentStreamIdentifier,
                        $nodeAggregateIdentifier,
                        $originDimensionSpacePoint,
                        false,
                        true
                    );
                    $change->addToDatabase($this->getDatabaseConnection());
                } else {
                    $change->moved = true;
                    $change->updateToDatabase($this->getDatabaseConnection());
                }
            }
        });
    }

    protected function getChange(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePoint $originDimensionSpacePoint)
    {
        $changeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentrepository_projection_change n
WHERE n.contentStreamIdentifier = :contentStreamIdentifier
AND n.nodeAggregateIdentifier = :nodeAggregateIdentifier
AND n.originDimensionSpacePointHash = :originDimensionSpacePointHash',
            [
                'contentStreamIdentifier' => $contentStreamIdentifier,
                'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->getHash()
            ]
        )->fetch();

        // We always allow root nodes
        return $changeRow ? Change::fromDatabaseRow($changeRow) : null;
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
