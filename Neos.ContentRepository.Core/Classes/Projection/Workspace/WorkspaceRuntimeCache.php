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

namespace Neos\ContentRepository\Core\Projection\Workspace;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Workspace Runtime Cache
 *
 * @internal
 */
final class WorkspaceRuntimeCache
{
    private bool $cacheEnabled = true;

    /**
     * @var array<string,Workspace>
     */
    private array $cachedWorkspacesByName = [];

    /**
     * @var array<string,Workspace>
     */
    private array $cachedWorkspacesByContentStreamId = [];

    /**
     * @return void
     */
    public function disableCache(): void
    {
        $this->cacheEnabled = false;
        $this->cachedWorkspacesByName = [];
        $this->cachedWorkspacesByContentStreamId = [];
    }

    public function getWorkspaceByName(WorkspaceName $name): ?Workspace
    {
        if ($this->cacheEnabled === true && isset($this->cachedWorkspacesByName[(string)$name])) {
            return $this->cachedWorkspacesByName[(string)$name];
        }
        return null;
    }

    public function setWorkspace(Workspace $workspace): void
    {
        if ($this->cacheEnabled === true) {
            $this->cachedWorkspacesByName[$workspace->workspaceName->name] = $workspace;
            $this->cachedWorkspacesByContentStreamId[
                $workspace->currentContentStreamId->value
            ] = $workspace;
        }
    }

    public function getByCurrentContentStreamId(ContentStreamId $contentStreamId): ?Workspace
    {
        if (
            $this->cacheEnabled === true
            && isset($this->cachedWorkspacesByContentStreamId[(string)$contentStreamId])
        ) {
            return $this->cachedWorkspacesByContentStreamId[(string)$contentStreamId];
        }
        return null;
    }
}
