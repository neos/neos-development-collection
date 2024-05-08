<?php

namespace Neos\ContentRepository\Core;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentStream\ContentStreamFinder;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal only command handlers
 */
class CommandHandlingDependencies
{
    /**
     * WorkspaceName->value to ContentGraphInterface
     * @var array<string, ContentGraphInterface>
     */
    private array $overridenContentGraphInstances = [];

    public function __construct(private readonly ContentRepository $contentRepository)
    {
    }

    public function handle(CommandInterface $command): CommandResult
    {
        return $this->contentRepository->handle($command);
    }

    public function getWorkspaceFinder(): WorkspaceFinder
    {
        return $this->contentRepository->getWorkspaceFinder();
    }

    public function getContentStreamFinder(): ContentStreamFinder
    {
        return $this->contentRepository->getContentStreamFinder();
    }

    public function getContentGraph(WorkspaceName $workspaceName): ContentGraphInterface
    {
        if (isset($this->overridenContentGraphInstances[$workspaceName->value])) {
            return $this->overridenContentGraphInstances[$workspaceName->value];
        }

        return $this->contentRepository->getContentGraph($workspaceName);
    }

    /**
     * Stateful (dirty) override of the chosen ContentStreamId for a given workspace, it applies within the given closure.
     * Implementations must ensure that requesting the contentStreamId for this workspace will resolve to the given
     * override ContentStreamId and vice versa resolving the WorkspaceName from this ContentStreamId should result in the
     * given WorkspaceName within the closure.
     *
     * @internal Used in write operations applying commands to a contentstream that will have WorkspaceName in the future
     * but doesn't have one yet.
     */
    public function overrideContentStreamId(WorkspaceName $workspaceName, ContentStreamId $contentStreamId, \Closure $fn): void
    {
        if (isset($this->overridenContentGraphInstances[$workspaceName->value])) {
            throw new \RuntimeException('Contentstream override for this workspace already in effect, nesting not allowed.', 1715170938);
        }

        $contentGraph = $this->contentRepository->projectionState(ContentGraphFinder::class)->getByWorkspaceNameAndContentStreamId($workspaceName, $contentStreamId);
        $this->overridenContentGraphInstances[$workspaceName->value] = $contentGraph;

        try {
            $fn();
        } finally {
            unset($this->overridenContentGraphInstances[$workspaceName->value]);
        }
    }
}
