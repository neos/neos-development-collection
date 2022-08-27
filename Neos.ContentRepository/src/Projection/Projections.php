<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Projection;

/**
 * An immutable set of Content Repository projections ({@see ProjectionInterface}
 *
 * @implements \IteratorAggregate<ProjectionInterface>
 * @internal
 */
final class Projections implements \IteratorAggregate
{
    /**
     * @var array<class-string<ProjectionInterface<ProjectionStateInterface>>, ProjectionInterface>
     * @phpstan-ignore-next-line
     */
    private array $projections;

    /**
     * @param array<class-string<ProjectionInterface<ProjectionStateInterface>>, ProjectionInterface> $projections
     * @phpstan-ignore-next-line
     */
    private function __construct(ProjectionInterface ...$projections)
    {
        // @phpstan-ignore-next-line
        $this->projections = $projections;
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @template T of ProjectionStateInterface
     * @param ProjectionInterface<T> $projection
     * @return self
     */
    public function with(ProjectionInterface $projection): self
    {
        if ($this->has($projection::class)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'a projection of type "%s" already exists in this set',
                    $projection::class
                ),
                1650121280
            );
        }
        $projections = $this->projections;
        $projections[$projection::class] = $projection;
        return new self(...$projections);
    }

    /**
     * @template T of ProjectionInterface
     * @param class-string<T> $projectionClassName
     * @return T
     */
    public function get(string $projectionClassName): ProjectionInterface
    {
        if (!$this->has($projectionClassName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'a projection of type "%s" is not registered in this content repository instance.',
                    $projectionClassName
                ),
                1650120813
            );
        }
        // @phpstan-ignore-next-line
        return $this->projections[$projectionClassName];
    }

    public function has(string $projectionClassName): bool
    {
        return array_key_exists($projectionClassName, $this->projections);
    }

    /**
     * @return \Traversable<ProjectionInterface>
     * @phpstan-ignore-next-line
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->projections);
    }
}
