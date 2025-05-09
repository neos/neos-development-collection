<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;

/**
 * Collection of all states (aka read models) of all projections for a Content Repository
 *
 * @internal
 */
final readonly class ProjectionStates
{
    /**
     * @param array<class-string<ProjectionStateInterface>, ProjectionStateInterface> $statesByClassName
     */
    private function __construct(
        private array $statesByClassName,
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
            if ($state instanceof ContentGraphReadModelInterface) {
                throw new \InvalidArgumentException(sprintf('The content graph state (%s) must not be part of the additional projection states', ContentGraphReadModelInterface::class), 1732390657);
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
        if ($className === ContentGraphReadModelInterface::class) {
            throw new \InvalidArgumentException(sprintf('Accessing the content repository projection state (%s) via is not allowed. Please use the API on the content repository instead.', ContentGraphReadModelInterface::class), 1732390824);
        }
        if (!array_key_exists($className, $this->statesByClassName)) {
            throw new \InvalidArgumentException(sprintf('A projection state of type "%s" is not registered in this content repository.', $className), 1662033650);
        }
        /** @var T $state */
        $state = $this->statesByClassName[$className];
        return $state;
    }
}
