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

namespace Neos\ContentRepository\Projection\ContentStream;

use Doctrine\DBAL\Connection;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Infrastructure\Projection\AbstractProcessedEventsAwareProjector;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
class ContentStreamProjector extends AbstractProcessedEventsAwareProjector
{
    private const TABLE_NAME = 'neos_contentrepository_projection_contentstream_v1';

    private DbalClientInterface $databaseClient;

    public function __construct(
        DbalClientInterface $eventStorageDatabaseClient,
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

    public function whenContentStreamWasCreated(ContentStreamWasCreated $event): void
    {
        $this->getDatabaseConnection()->insert(self::TABLE_NAME, [
            'contentStreamIdentifier' => $event->getContentStreamIdentifier(),
            'state' => ContentStreamFinder::STATE_CREATED,
        ]);
    }

    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        // the content stream is in use now
        $this->getDatabaseConnection()->update(self::TABLE_NAME, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->getNewContentStreamIdentifier()
        ]);
    }

    public function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        // the content stream is in use now
        $this->getDatabaseConnection()->update(self::TABLE_NAME, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->getNewContentStreamIdentifier()
        ]);
    }

    public function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->getDatabaseConnection()->insert(self::TABLE_NAME, [
            'contentStreamIdentifier' => $event->getContentStreamIdentifier(),
            'sourceContentStreamIdentifier' => $event->getSourceContentStreamIdentifier(),
            'state' => ContentStreamFinder::STATE_REBASING, // TODO: FORKED?
        ]);
    }

    public function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
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

    public function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
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

    public function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
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

    public function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
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

    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
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

    public function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        $this->updateStateForContentStream(
            $event->getCandidateContentStreamIdentifier(),
            ContentStreamFinder::STATE_REBASE_ERROR
        );
    }

    public function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        $this->getDatabaseConnection()->update(self::TABLE_NAME, [
            'removed' => true
        ], [
            'contentStreamIdentifier' => $event->getContentStreamIdentifier()
        ]);
    }

    private function updateStateForContentStream(ContentStreamIdentifier $contentStreamIdentifier, string $state): void
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
    protected function transactional(\Closure $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->databaseClient->getConnection();
    }
}
