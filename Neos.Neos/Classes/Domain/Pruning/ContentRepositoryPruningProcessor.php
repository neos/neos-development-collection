<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Pruning;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\EventStore\EventStoreInterface;

/**
 * Pruning processor that removes all events from the given cr
 */
final readonly class ContentRepositoryPruningProcessor implements ProcessorInterface, ContentRepositoryServiceInterface
{
    public function __construct(
        private ContentRepository $contentRepository,
        private EventStoreInterface $eventStore,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        foreach ($this->contentRepository->findContentStreams() as $contentStream) {
            /** @phpstan-ignore-next-line calling internal method */
            $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStream->id)->getEventStreamName();
            $this->eventStore->deleteStream($streamName);
        }
        foreach ($this->contentRepository->findWorkspaces() as $workspace) {
            /** @phpstan-ignore-next-line calling internal method */
            $streamName = WorkspaceEventStreamName::fromWorkspaceName($workspace->workspaceName)->getEventStreamName();
            $this->eventStore->deleteStream($streamName);
        }
    }
}
