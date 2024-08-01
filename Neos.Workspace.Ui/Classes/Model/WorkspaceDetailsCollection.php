<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Model;

/**
 * Collection of details for workspaces for the workspace list in the UI
 */
final readonly class WorkspaceDetailsCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array{total: int, internal: int, private: int}
     */
    public array $counts;

    /**
     * @param array<string, WorkspaceDetails> $items
     */
    public function __construct(
        public array $items = []
    ) {
        $this->counts = [
            'total' => $this->count(),
            'internal' => count(
                array_filter(
                    $this->items,
                    static fn(WorkspaceDetails $workspaceDetails) => $workspaceDetails->workspace->isInternalWorkspace()
                )
            ),
            'private' => count(
                array_filter(
                    $this->items,
                    static fn(WorkspaceDetails $workspaceDetails) => $workspaceDetails->workspace->isPrivateWorkspace()
                )
            ),
        ];
    }

    /**
     * @return \Traversable<WorkspaceDetails>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
