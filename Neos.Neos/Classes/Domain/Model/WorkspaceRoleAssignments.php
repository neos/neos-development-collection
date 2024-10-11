<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Traversable;

/**
 * A set of {@see WorkspaceRoleAssignment} instances
 *
 * @implements \IteratorAggregate<WorkspaceRoleAssignment>
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceRoleAssignments implements \IteratorAggregate, \Countable
{
    /**
     * @var array<WorkspaceRoleAssignment>
     */
    private array $assignments;

    private function __construct(WorkspaceRoleAssignment ...$assignments)
    {
        $this->assignments = $assignments;
    }

    /**
     * @param array<WorkspaceRoleAssignment> $assignments
     */
    public static function fromArray(array $assignments): self
    {
        return new self(...$assignments);
    }

    public function isEmpty(): bool
    {
        return $this->assignments === [];
    }

    public function getIterator(): Traversable
    {
        yield from $this->assignments;
    }

    public function count(): int
    {
        return count($this->assignments);
    }
}
