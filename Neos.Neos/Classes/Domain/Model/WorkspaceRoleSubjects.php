<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * A set of {@see WorkspaceRoleSubject} instances
 *
 * @implements \IteratorAggregate<WorkspaceRoleSubject>
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceRoleSubjects implements \IteratorAggregate, \Countable
{
    /**
     * @var array<WorkspaceRoleSubject>
     */
    private array $subjects;

    private function __construct(WorkspaceRoleSubject ...$subjects)
    {
        $this->subjects = $subjects;
    }

    /**
     * @param array<WorkspaceRoleSubject> $subjects
     */
    public static function fromArray(array $subjects): self
    {
        return new self(...$subjects);
    }

    public function isEmpty(): bool
    {
        return $this->subjects === [];
    }

    public function getIterator(): \Traversable
    {
        yield from $this->subjects;
    }

    public function count(): int
    {
        return count($this->subjects);
    }
}
