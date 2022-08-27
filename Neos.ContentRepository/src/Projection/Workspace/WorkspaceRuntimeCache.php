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

namespace Neos\ContentRepository\Projection\Workspace;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;

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
    private array $cachedWorkspacesByContentStreamIdentifier = [];

    /**
     * @return void
     */
    public function disableCache(): void
    {
        $this->cacheEnabled = false;
        $this->cachedWorkspacesByName = [];
        $this->cachedWorkspacesByContentStreamIdentifier = [];
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
            $this->cachedWorkspacesByContentStreamIdentifier[
                $workspace->currentContentStreamIdentifier->getValue()
            ] = $workspace;
        }
    }

    public function getByCurrentContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): ?Workspace
    {
        if (
            $this->cacheEnabled === true
            && isset($this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier])
        ) {
            return $this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier];
        }
        return null;
    }
}
