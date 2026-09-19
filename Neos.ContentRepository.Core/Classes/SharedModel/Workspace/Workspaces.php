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
final readonly class Workspaces implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string,Workspace>
     */
    private array $workspaces;

    /**
     * @var array<string,list<Workspace>>
     */
    private array $workspacesByBaseWorkspaceName;

    /**
     * @var list<Workspace>
     */
    private array $rootWorkspaces;

    /**
     * @param iterable<Workspace> $collection
     */
    private function __construct(iterable $collection)
    {
        $workspaces = [];
        $rootWorkspaces = [];
        $workspacesByBaseWorkspaceName = [];
        foreach ($collection as $item) {
            if (!$item instanceof Workspace) {
                throw new \InvalidArgumentException(sprintf('Workspaces must only consist of %s objects, got: %s', Workspace::class, get_debug_type($item)), 1677833509);
            }
            $workspaces[$item->workspaceName->value] = $item;
            if ($item->baseWorkspaceName !== null) {
                $workspacesByBaseWorkspaceName[$item->baseWorkspaceName->value][] = $item;
            } else {
                $rootWorkspaces[] = $item;
            }
        }

        $this->workspaces = $workspaces;
        $this->rootWorkspaces = $rootWorkspaces;
        $this->workspacesByBaseWorkspaceName = $workspacesByBaseWorkspaceName;
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
     * @return list<Workspace>
     */
    public function getRootWorkspaces(): array
    {
        return $this->rootWorkspaces;
    }

    /**
     * Get all base workspaces (if they are included in this set).
     */
    public function getBaseWorkspaces(WorkspaceName $workspaceName): Workspaces
    {
        $bases = [];
        $workspace = $this->workspaces[$workspaceName->value] ?? null;
        while ($workspace?->baseWorkspaceName !== null) {
            $workspace = $this->workspaces[$workspace->baseWorkspaceName->value] ?? null;
            // base workspace might not be in this set!
            if ($workspace !== null) {
                $bases[] = $workspace;
            }
        }
        return Workspaces::fromArray($bases);
    }

    /**
     * Get all dependent workspaces recursively (if they are included in this set).
     */
    public function getDependantWorkspacesRecursively(WorkspaceName $workspaceName): Workspaces
    {
        $dependants = [];
        $stack = $this->workspacesByBaseWorkspaceName[$workspaceName->value] ?? [];
        while ($stack !== []) {
            $workspace = array_shift($stack);
            $dependants[] = $workspace;
            $stack = [...$stack, ...$this->workspacesByBaseWorkspaceName[$workspace->workspaceName->value] ?? []];
        }
        return self::fromArray($dependants);
    }

    /**
     * Get all immediately dependent workspaces (if they are included in this set).
     */
    public function getDependantWorkspaces(WorkspaceName $workspaceName): Workspaces
    {
        return self::fromArray($this->workspacesByBaseWorkspaceName[$workspaceName->value] ?? []);
    }

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

    /**
     * @template T
     * @param \Closure(Workspace): T $callback
     * @return list<T>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, array_values($this->workspaces));
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
