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

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * An immutable, type-safe collection of Workspace objects
 *
 * @implements \IteratorAggregate<int,Workspace>
 *
 * @api
 */

final class Workspaces implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string,Workspace>
     */
    private array $workspaces;

    /**
     * @param iterable<mixed,Workspace> $collection
     */
    private function __construct(iterable $collection)
    {
        $workspaces = [];
        foreach ($collection as $item) {
            if (!$item instanceof Workspace) {
                throw new \InvalidArgumentException(
                    'Workspaces can only consist of ' . Workspace::class . ' objects.',
                    1677833509
                );
            }
            $workspaces[$item->workspaceName->name] = $item;
        }

        $this->workspaces = $workspaces;
    }

    /**
     * @param array<mixed,Workspace> $workspaces
     */
    public static function fromArray(array $workspaces): self
    {
        return new self($workspaces);
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public function get(WorkspaceName $workspaceName): ?Workspace
    {
        return $this->workspaces[$workspaceName->name] ?? null;
    }

    /**
     * Get all base workspaces (if they are included in this result set).
     *
     * @param WorkspaceName $workspaceName
     * @return Workspaces
     */
    public function getBaseWorkspaces(WorkspaceName $workspaceName): Workspaces
    {
        $baseWorkspaces = [];

        $workspace = $this->get($workspaceName);
        if (!$workspace) {
            return Workspaces::createEmpty();
        }
        $baseWorkspaceName = $workspace->baseWorkspaceName;
        while ($baseWorkspaceName != null) {
            $baseWorkspace = $this->get($baseWorkspaceName);
            if ($baseWorkspace) {
                $baseWorkspaces[] = $baseWorkspace;
                $baseWorkspaceName = $baseWorkspace->baseWorkspaceName;
            } else {
                $baseWorkspaceName = null;
            }
        }
        return Workspaces::fromArray($baseWorkspaces);
    }

    /**
     * @return \ArrayIterator<int,Workspace>|Workspace[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator(array_values($this->workspaces));
    }

    public function count(): int
    {
        return count($this->workspaces);
    }
}
