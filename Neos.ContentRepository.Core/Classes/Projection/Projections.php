<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\Subscription\SubscriptionId;

/**
 * An immutable set of Content Repository projections ({@see ProjectionInterface}
 *
 * @implements \IteratorAggregate<ProjectionInterface<ProjectionStateInterface>>
 * @internal
 */
final class Projections implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, ProjectionInterface<ProjectionStateInterface>>
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

    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @param array<string, ProjectionInterface<ProjectionStateInterface>> $projections
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
     * @return ProjectionInterface<ProjectionStateInterface>
     */
    public function get(SubscriptionId $id): ProjectionInterface
    {
        if (!$this->has($id)) {
            throw new \InvalidArgumentException(sprintf('a projection with id "%s" is not registered in this content repository instance.', $id->value), 1650120813);
        }
        return $this->projections[$id->value];
    }

    public function has(SubscriptionId $id): bool
    {
        return array_key_exists($id->value, $this->projections);
    }

    /**
     * @param ProjectionInterface<ProjectionStateInterface> $projection
     * @return self
     */
    public function with(SubscriptionId $id, ProjectionInterface $projection): self
    {
        return self::fromArray([...$this->projections, ...[$id->value => $projection]]);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->projections;
    }

    public function count(): int
    {
        return count($this->projections);
    }
}
