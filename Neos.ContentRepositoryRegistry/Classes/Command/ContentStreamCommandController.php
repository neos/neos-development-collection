<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Core\Service\ContentStreamPrunerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

class ContentStreamCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * Detects if dangling content streams exists and which content streams could be pruned from the event stream
     *
     * Dangling content streams
     * ------------------------
     *
     * Content streams that are not removed via the event ContentStreamWasRemoved and are not in use by a workspace
     * (not a current's workspace content stream).
     *
     * Previously before Neos 9 beta 15 (#5301), dangling content streams were not removed during publishing, discard or rebase.
     *
     * ./flow contentStream:removeDangling
     *
     * Pruneable content streams
     * -------------------------
     *
     * Content streams that were removed ContentStreamWasRemoved e.g. after publishing, and are not required for a full
     * replay to reconstruct the current projections state. The ability to reconstitute a previous state will be lost.
     *
     * ./flow contentStream:pruneRemovedFromEventStream
     *
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     */
    public function statusCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentStreamPruner = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new ContentStreamPrunerFactory());

        $status = $contentStreamPruner->outputStatus(
            $this->outputLine(...)
        );
        if ($status === false) {
            $this->quit(1);
        }
    }

    /**
     * Removes all nodes, hierarchy relations and content stream entries which are not needed anymore from the projections.
     *
     * NOTE: This still **keeps** the event stream as is; so it would be possible to re-construct the content stream at a later point in time.
     *
     * HINT: ./flow contentStream:status gives information what is about to be removed
     *
     * To prune the removed content streams from the event stream, run ./flow contentStream:pruneRemovedFromEventStream afterwards.
     *
     * NOTE: To ensure that no temporary content streams of the *current* moment are removed, a time threshold is configurable via --remove-temporary-before
     *
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @param string $removeTemporaryBefore includes all temporary content streams like FORKED or CREATED older than that in the removal. To remove all use --remove-temporary-before=-1sec
     */
    public function removeDanglingCommand(string $contentRepository = 'default', string $removeTemporaryBefore = '-1day'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentStreamPruner = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new ContentStreamPrunerFactory());

        try {
            $removeTemporaryBeforeDate = new \DateTimeImmutable($removeTemporaryBefore);
        } catch (\Exception $exception) {
            $this->outputLine(sprintf('<error>--remove-temporary-before=%s is not a valid date</error>: %s', $removeTemporaryBefore, $exception->getMessage()));
            $this->quit(1);
        }

        $now = new \DateTimeImmutable('now');
        if ($removeTemporaryBeforeDate > $now) {
            $this->outputLine(sprintf('<error>--remove-temporary-before=%s must be in the past</error>', $removeTemporaryBefore));
            $this->quit(1);
        }

        $contentStreamPruner->removeDanglingContentStreams(
            $this->outputLine(...),
            $removeTemporaryBeforeDate
        );
    }

    /**
     * Prune removed content streams that are unused from the event stream; effectively REMOVING information completely
     *
     * HINT: ./flow contentStream:status gives information what is about to be pruned
     *
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @param bool $force Prune the unused content streams without confirmation. This cannot be reverted!
     */
    public function pruneRemovedFromEventStreamCommand(string $contentRepository = 'default', bool $force = false): void
    {
        if (!$force && !$this->output->askConfirmation(sprintf('> This will prune removed content streams that are unused from the event stream in content repository "%s" (see flow contentStream:status). Are you sure to proceed? (y/n) ', $contentRepository), false)) {
            $this->outputLine('<comment>Abort.</comment>');
            return;
        }

        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentStreamPruner = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new ContentStreamPrunerFactory());

        $contentStreamPruner->pruneRemovedFromEventStream(
            $this->outputLine(...)
        );
    }
}
