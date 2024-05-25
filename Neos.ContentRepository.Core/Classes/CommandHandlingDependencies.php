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
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamState;
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
    private array $overridenContentGraphInstances = [];

    public function __construct(private readonly ContentRepository $contentRepository)
    {
    }

    public function handle(CommandInterface $command): CommandResult
    {
        return $this->contentRepository->handle($command);
    }

    public function getContentStreamVersion(ContentStreamId $contentStreamId): Version
    {
        // TODO implement
        return Version::fromInteger(1);
    }

    public function contentStreamExists(ContentStreamId $contentStreamId): bool
    {
        // TODO implement
        return false;
    }

    public function getContentStreamState(ContentStreamId $contentStreamId): ContentStreamState
    {
        // TODO implement
        return ContentStreamState::STATE_FORKED;
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        // TODO implement
        return null;
    }

    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     */
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
