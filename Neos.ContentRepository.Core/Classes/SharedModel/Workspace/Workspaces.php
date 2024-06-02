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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

/**
 * An immutable, type-safe collection of Workspace objects
 *
 * @implements \IteratorAggregate<Workspace>
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
     * @param iterable<Workspace> $collection
     */
    private function __construct(iterable $collection)
    {
        $workspaces = [];
        foreach ($collection as $item) {
            if (!$item instanceof Workspace) {
                throw new \InvalidArgumentException(sprintf('Workspaces must only consist of %s objects, got: %s', Workspace::class, get_debug_type($item)), 1677833509);
            }
            $workspaces[$item->workspaceName->value] = $item;
        }

        $this->workspaces = $workspaces;
    }

    /**
     * @param array<Workspace> $workspaces
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
        return $this->workspaces[$workspaceName->value] ?? null;
    }

    /**
     * Get all base workspaces (if they are included in this result set).
     */
    public function getBaseWorkspaces(WorkspaceName $workspaceName): Workspaces
    {
        $baseWorkspaces = [];

        $workspace = $this->get($workspaceName);
        if (!$workspace) {
            return self::createEmpty();
        }
        $baseWorkspaceName = $workspace->baseWorkspaceName;
        while ($baseWorkspaceName !== null) {
            $baseWorkspace = $this->get($baseWorkspaceName);
            if ($baseWorkspace) {
                $baseWorkspaces[] = $baseWorkspace;
                $baseWorkspaceName = $baseWorkspace->baseWorkspaceName;
            } else {
                $baseWorkspaceName = null;
            }
        }
        return self::fromArray($baseWorkspaces);
    }

    /**
     * @return \Traversable<Workspace>
     */
    public function getIterator(): \Traversable
    {
        yield from array_values($this->workspaces);
    }

    /**
     * @param \Closure(Workspace): bool $callback
     */
    public function filter(\Closure $callback): self
    {
        return new self(array_filter($this->workspaces, $callback));
    }

    /**
     * @param \Closure(Workspace): bool $callback
     */
    public function find(\Closure $callback): ?Workspace
    {
        foreach ($this->workspaces as $workspace) {
            if ($callback($workspace)) {
                return $workspace;
            }
        }
        return null;
    }

    public function count(): int
    {
        return count($this->workspaces);
    }

    public function isEmpty(): bool
    {
        return $this->workspaces === [];
    }
}
