<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Projection\ContentStream;

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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceRebaseFailed;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasDiscarded;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasPartiallyDiscarded;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasPartiallyPublished;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasPublished;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasRebased;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\AbstractProcessedEventsAwareProjector;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class ContentStreamProjector extends AbstractProcessedEventsAwareProjector
{
    private const TABLE_NAME = 'neos_contentrepository_projection_contentstream_v1';

    private DbalClient $databaseClient;

    public function __construct(
        DbalClient $eventStorageDatabaseClient,
        VariableFrontend $processedEventsCache
    ) {
        $this->databaseClient = $eventStorageDatabaseClient;
        parent::__construct($eventStorageDatabaseClient, $processedEventsCache);
    }

    public function reset(): void
    {
        parent::reset();
        $this->getDatabaseConnection()->exec('TRUNCATE ' . self::TABLE_NAME);
    }

    public function whenContentStreamWasCreated(ContentStreamWasCreated $event)
    {
        $this->getDatabaseConnection()->insert(self::TABLE_NAME, [
            'contentStreamIdentifier' => $event->getContentStreamIdentifier(),
            'state' => ContentStreamFinder::STATE_CREATED,
        ]);
    }

    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event)
    {
        // the content stream is in use now
        $this->getDatabaseConnection()->update(self::TABLE_NAME, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->getNewContentStreamIdentifier()
        ]);
    }

    public function whenWorkspaceWasCreated(WorkspaceWasCreated $event)
    {
        // the content stream is in use now
        $this->getDatabaseConnection()->update(self::TABLE_NAME, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->getNewContentStreamIdentifier()
        ]);
    }

    public function whenContentStreamWasForked(ContentStreamWasForked $event)
    {
        $this->getDatabaseConnection()->insert(self::TABLE_NAME, [
            'contentStreamIdentifier' => $event->getContentStreamIdentifier(),
            'sourceContentStreamIdentifier' => $event->getSourceContentStreamIdentifier(),
            'state' => ContentStreamFinder::STATE_REBASING, // TODO: FORKED?
        ]);
    }

    public function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event)
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event)
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event)
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewSourceContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousSourceContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceWasPublished(WorkspaceWasPublished $event)
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewSourceContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousSourceContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event)
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event)
    {
        $this->updateStateForContentStream(
            $event->getCandidateContentStreamIdentifier(),
            ContentStreamFinder::STATE_REBASE_ERROR
        );
    }

    public function whenContentStreamWasRemoved(ContentStreamWasRemoved $event)
    {
        $this->getDatabaseConnection()->update(self::TABLE_NAME, [
            'removed' => true
        ], [
            'contentStreamIdentifier' => $event->getContentStreamIdentifier()
        ]);
    }


    private function updateStateForContentStream(ContentStreamIdentifier $contentStreamIdentifier, string $state)
    {
        $this->getDatabaseConnection()->update(self::TABLE_NAME, [
            'state' => $state,
        ], [
            'contentStreamIdentifier' => $contentStreamIdentifier
        ]);
    }

    /**
     * @throws \Throwable
     */
    protected function transactional(callable $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->databaseClient->getConnection();
    }
}
