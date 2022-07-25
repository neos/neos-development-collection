<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
final class WorkspaceCommandHandlerObjectFactory
{
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler,
        private readonly ContentStreamCommandHandler $contentStreamCommandHandler,
        private readonly NodeDuplicationCommandHandler $nodeDuplicationCommandHandler,
        private readonly ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        private readonly ContentGraphInterface $contentGraph,
        private readonly RuntimeBlocker $runtimeBlocker
    ) {}

    public function buildWorkspaceCommandHandler()
    {
        return new WorkspaceCommandHandler(
            $this->eventStore,
            $this->workspaceFinder,
            $this->nodeAggregateCommandHandler,
            $this->contentStreamCommandHandler,
            $this->nodeDuplicationCommandHandler,
            $this->readSideMemoryCacheManager,
            $this->contentGraph,
            $this->runtimeBlocker
        );
    }
}
