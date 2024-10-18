<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

/**
 * @api
 * @implements \IteratorAggregate<ProjectionStatus>
 */
final readonly class ProjectionStatuses implements \IteratorAggregate
{
    /**
     * @param array<class-string<ProjectionInterface<ProjectionStateInterface>>, ProjectionStatus> $statuses
     */
    private function __construct(
        public array $statuses,
    ) {
    }

    public static function create(): self
    {
        return new self([]);
    }

    /**
     * @param class-string<ProjectionInterface<ProjectionStateInterface>> $projectionClassName
     */
    public function with(string $projectionClassName, ProjectionStatus $projectionStatus): self
    {
        $statuses = $this->statuses;
        $statuses[$projectionClassName] = $projectionStatus;
        return new self($statuses);
    }


    public function getIterator(): \Traversable
    {
        yield from $this->statuses;
    }
}
