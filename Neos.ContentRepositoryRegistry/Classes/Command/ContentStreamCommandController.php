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
     * Remove all content streams which are not needed anymore from the projections.
     *
     * NOTE: This still **keeps** the event stream as is; so it would be possible to re-construct the content stream
     *       at a later point in time (though we currently do not provide any API for it).
     *
     *       To remove the deleted Content Streams, use `./flow contentStream:pruneRemovedFromEventStream` after running
     *       `./flow contentStream:prune`.
     *
     * By default, only content streams that are NO_LONGER_IN_USE will be removed.
     * If you also call with "--removeTemporary", will delete ALL content streams which are currently not assigned
     * to a workspace (f.e. dangling ones in FORKED or CREATED.).
     *
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @param boolean $removeTemporary Will delete all content streams which are currently not assigned (Default: false)
     */
    public function pruneCommand(string $contentRepository = 'default', bool $removeTemporary = false): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentStreamPruner = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new ContentStreamPrunerFactory());

        $contentStreamPruner->prune(
            $removeTemporary,
            $this->outputLine(...)
        );
    }

    /**
     * Remove unused and deleted content streams from the event stream; effectively REMOVING information completely
     *
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     */
    public function pruneRemovedFromEventStreamCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentStreamPruner = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new ContentStreamPrunerFactory());

        $unusedContentStreamNames = $contentStreamPruner->pruneRemovedFromEventStream();
        $unusedContentStreamsPresent = false;
        foreach ($unusedContentStreamNames as $contentStreamName) {
            $this->outputFormatted('Removed events for %s', [$contentStreamName->value]);
            $unusedContentStreamsPresent = true;
        }
        if (!$unusedContentStreamsPresent) {
            $this->outputLine('There are no unused content streams.');
        }
    }
}
