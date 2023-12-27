<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

/**
 * An immutable set of Content Repository projections ({@see ProjectionInterface}
 *
 * @implements \IteratorAggregate<ProjectionInterface>
 * @internal
 */
final class Projections implements \IteratorAggregate
{
    /**
     * @var array<class-string<ProjectionInterface<ProjectionStateInterface>>, ProjectionInterface<ProjectionStateInterface>>
     */
    private array $projections;

    /**
     * @param ProjectionInterface<ProjectionStateInterface> ...$projections
     */
    private function __construct(ProjectionInterface ...$projections)
    {
        // @phpstan-ignore-next-line
        $this->projections = $projections;
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @param array<ProjectionInterface<ProjectionStateInterface>> $projections
     * @return self
     */
    public static function fromArray(array $projections): self
    {
        $projectionsByClassName = [];
        foreach ($projections as $projection) {
            if (array_key_exists($projection::class, $projectionsByClassName)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'a projection of type "%s" already exists in this set',
                        $projection::class
                    ),
                    1650121280
                );
            }
            $projectionsByClassName[$projection::class] = $projection;
        }
        return new self(...$projectionsByClassName);
    }

    /**
     * @template T of ProjectionInterface
     * @param class-string<T> $projectionClassName
     * @return T
     */
    public function get(string $projectionClassName): ProjectionInterface
    {
        $projection = $this->projections[$projectionClassName] ?? null;
        if (!$projection instanceof $projectionClassName) {
            throw new \InvalidArgumentException(
                sprintf(
                    'a projection of type "%s" is not registered in this content repository instance.',
                    $projectionClassName
                ),
                1650120813
            );
        }
        return $projection;
    }

    public function has(string $projectionClassName): bool
    {
        return array_key_exists($projectionClassName, $this->projections);
    }

    /**
     * @return list<class-string<ProjectionInterface<ProjectionStateInterface>>>
     */
    public function getClassNames(): array
    {
        return array_keys($this->projections);
    }

    /**
     * @return \Traversable<ProjectionInterface<ProjectionStateInterface>>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->projections;
    }
}
