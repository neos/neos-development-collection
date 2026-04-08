<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

/**
 * @implements \IteratorAggregate<int,WorkspaceName>
 * @api
 */
final readonly class WorkspaceNames implements \IteratorAggregate, \Countable
{
    /** @param array<string,WorkspaceName> $items */
    private function __construct(
        private array $items
    ) {
    }

    public static function create(
        WorkspaceName ...$items
    ): self {
        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item->value] = $item;
        }
        return new self(
            $indexed
        );
    }

    public static function fromWorkspaces(Workspaces $workspaces): self
    {
        return WorkspaceNames::create(...$workspaces->map(fn (Workspace $workspace) => $workspace->workspaceName));
    }

    public function contain(WorkspaceName $workspaceName): bool
    {
        return array_key_exists($workspaceName->value, $this->items);
    }

    /** @return list<string> */
    public function toStringArray(): array
    {
        return array_keys($this->items);
    }

    public function getIterator(): \Traversable
    {
        yield from array_values($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
