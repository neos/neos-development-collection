<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\Event\Version;

/**
 * An adapter to provide aceess to read projection data and delegate (sub) commands
 *
 * @internal only command handlers are provided with this via the
 * @see ContentRepository::handle()
 */
final class CommandHandlingDependencies
{
    /**
     * WorkspaceName->value to ContentGraphInterface
     * @var array<string, ContentGraphInterface>
     */
    private array $overriddenContentGraphInstances = [];

    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentRepositoryReadModel $adapter
    ) {
    }

    public function handle(CommandInterface $command): CommandResult
    {
        return $this->contentRepository->handle($command);
    }

    public function getContentStreamVersion(ContentStreamId $contentStreamId): Version
    {
        $contentStream = $this->adapter->findContentStreamById($contentStreamId);
        if ($contentStream === null) {
            throw new \InvalidArgumentException(sprintf('Failed to find content stream with id "%s"', $contentStreamId->value), 1716902051);
        }
        return $contentStream->version;
    }

    public function contentStreamExists(ContentStreamId $contentStreamId): bool
    {
        return $this->adapter->findContentStreamById($contentStreamId) !== null;
    }

    public function getContentStreamStatus(ContentStreamId $contentStreamId): ContentStreamStatus
    {
        $contentStream = $this->adapter->findContentStreamById($contentStreamId);
        if ($contentStream === null) {
            throw new \InvalidArgumentException(sprintf('Failed to find content stream with id "%s"', $contentStreamId->value), 1716902219);
        }
        return $contentStream->status;
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        return $this->adapter->findWorkspaceByName($workspaceName);
    }

    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     */
    public function getContentGraph(WorkspaceName $workspaceName): ContentGraphInterface
    {
        if (isset($this->overriddenContentGraphInstances[$workspaceName->value])) {
            return $this->overriddenContentGraphInstances[$workspaceName->value];
        }
        $workspace = $this->adapter->findWorkspaceByName($workspaceName);
        if ($workspace === null) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }
        return $this->adapter->buildContentGraph($workspace->workspaceName, $workspace->currentContentStreamId);
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
        if (isset($this->overriddenContentGraphInstances[$workspaceName->value])) {
            throw new \RuntimeException('Contentstream override for this workspace already in effect, nesting not allowed.', 1715170938);
        }

        $contentGraph = $this->adapter->buildContentGraph($workspaceName, $contentStreamId);
        $this->overriddenContentGraphInstances[$workspaceName->value] = $contentGraph;

        try {
            $fn();
        } finally {
            unset($this->overriddenContentGraphInstances[$workspaceName->value]);
        }
    }
}
