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

namespace Neos\Neos\Domain\Import;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Severity;
use Neos\EventStore\EventStoreInterface;

/**
 * Import processor that ensurs that an existing Live workspace is empty
 */
final readonly class LiveWorkspaceIsEmptyProcessor implements ProcessorInterface, ContentRepositoryServiceInterface
{
    public function __construct(
        private EventStoreInterface $eventStore,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $context->dispatch(Severity::NOTICE, 'Ensures empty live workspace');

        if ($this->workspaceHasEvents(WorkspaceName::forLive())) {
            throw new \RuntimeException('Live workspace already contains events please run "cr:prune" before importing.');
        }
    }

    private function workspaceHasEvents(WorkspaceName $workspaceName): bool
    {
        /** @phpstan-ignore-next-line internal method of the cr is called */
        $workspaceStreamName = WorkspaceEventStreamName::fromWorkspaceName($workspaceName)->getEventStreamName();
        $eventStream = $this->eventStore->load($workspaceStreamName);
        foreach ($eventStream as $event) {
            return true;
        }
        return false;
    }
}
