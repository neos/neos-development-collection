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

    /**
     * @param array<class-string<ProjectionInterface<ProjectionStateInterface>>, ProjectionStatus> $statuses
     */
    public static function create(array $statuses = []): self
    {
        return new self($statuses);
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
        return new \ArrayIterator($this->statuses);
    }
}
