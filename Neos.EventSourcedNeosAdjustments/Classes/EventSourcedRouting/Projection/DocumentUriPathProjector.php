<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasMoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class DocumentUriPathProjector implements ProjectorInterface
{
    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @var string
     */
    private $liveContentStreamIdentifierRuntimeCache;

    public function injectEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->dbal = $entityManager->getConnection();
    }

    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->dbal->insert('document_uri_livecontentstreams', [
            'contentStreamIdentifier' => $event->getNewContentStreamIdentifier(),
            'workspaceName' => $event->getWorkspaceName(),
        ]);
    }

    public function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
    {
        // TODO implement
    }

    public function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        if ((string)$event->getContentStreamIdentifier() !== $this->getLiveContentStreamIdentifier()) {
            return;
        }
        $uriPathSegment = $event->getInitialPropertyValues()->getPlainValues()['uriPathSegment'] ?? null;
        if ($uriPathSegment === null) {
            return;
        }

        // TODO transaction

        /** @var array $parentNodePathInfo */
        $parentNodePathInfo = $this->dbal->fetchAssoc('SELECT uriPath, nodePath FROM document_uri WHERE dimensionSpacePointHash = :dimensionSpacePointHash AND nodeAggregateIdentifier = :nodeAggregateIdentifier LIMIT 1', [
            'dimensionSpacePointHash' => $event->getOriginDimensionSpacePoint()->getHash(),
            'nodeAggregateIdentifier' => $event->getParentNodeAggregateIdentifier(),
        ]);
        $nodeName = $event->getNodeName() !== null ? (string)$event->getNodeName() : (string)$event->getNodeAggregateIdentifier();
        $nodePath = ($parentNodePathInfo !== false ? $parentNodePathInfo['nodePath'] . '/' : '') . $nodeName;
        $uriPath = ($parentNodePathInfo !== false ? $parentNodePathInfo['uriPath'] . '/' : '') . $uriPathSegment;

        foreach ($event->getCoveredDimensionSpacePoints() as $dimensionSpacePoint) {
            $this->dbal->insert('document_uri', [
                'uriPath' => $uriPath,
                'nodePath' => $nodePath,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                'nodeAggregateIdentifier' => $event->getNodeAggregateIdentifier(),
            ]);
        }
    }

    public function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        // TODO implement
    }

    public function reset(): void
    {
        $this->dbal->exec('TRUNCATE document_uri');
        $this->dbal->exec('TRUNCATE document_uri_livecontentstreams');
    }

    private function getLiveContentStreamIdentifier(): string
    {
        if ($this->liveContentStreamIdentifierRuntimeCache === null) {
            $this->liveContentStreamIdentifierRuntimeCache = $this->dbal->fetchColumn('SELECT contentStreamIdentifier FROM document_uri_livecontentstreams');
        }
        return $this->liveContentStreamIdentifierRuntimeCache;
    }
}
