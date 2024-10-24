<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

/**
 * Collection of all states (aka read models) of all projections for a Content Repository
 *
 * @internal
 * @implements \IteratorAggregate<ProjectionStateInterface>
 */
final readonly class ProjectionStates implements \IteratorAggregate, \Countable
{
    /**
     * @param array<class-string<ProjectionStateInterface>, ProjectionStateInterface> $statesByClassName
     */
    private function __construct(
        public array $statesByClassName,
    ) {
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @param array<ProjectionStateInterface> $states
     */
    public static function fromArray(array $states): self
    {
        $statesByClassName = [];
        foreach ($states as $state) {
            if (!$state instanceof ProjectionStateInterface) {
                throw new \InvalidArgumentException(sprintf('Expected instance of %s, got: %s', ProjectionStateInterface::class, get_debug_type($state)), 1729687661);
            }
            if (array_key_exists($state::class, $statesByClassName)) {
                throw new \InvalidArgumentException(sprintf('An instance of %s is already part of the set', $state::class), 1729687716);
            }
            $statesByClassName[$state::class] = $state;
        }
        return new self($statesByClassName);
    }

    /**
     * Retrieve a single state (aka read model) by its fully qualified PHP class name
     *
     * @template T of ProjectionStateInterface
     * @param class-string<T> $className
     * @return T
     * @throws \InvalidArgumentException if the specified state class is not registered
     */
    public function get(string $className): ProjectionStateInterface
    {
        if (!array_key_exists($className, $this->statesByClassName)) {
            throw new \InvalidArgumentException(sprintf('The state class "%s" does not exist.', $className), 1729687836);
        }
        return $this->statesByClassName[$className];
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->statesByClassName);
    }

    public function count(): int
    {
        return count($this->statesByClassName);
    }
}
