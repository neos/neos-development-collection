<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection;

final class CatchUpHandlerFactories implements CatchUpHandlerFactoryInterface
{
    /**
     * @var array<class-string<ProjectionInterface<ProjectionStateInterface>>, ProjectionInterface>
     */
    private array $catchUpHandlerFactories;

    /**
     * @param array<class-string<ProjectionInterface<ProjectionStateInterface>>, ProjectionInterface> $projections
     */
    private function __construct(ProjectionInterface ...$projections)
    {
        $this->catchUpHandlerFactories = $projections;
    }

    public static function create(): self
    {
        return new self();
    }

    public function with(CatchUpHandlerFactoryInterface $catchUpHandlerFactory): self
    {
        if ($this->has($catchUpHandlerFactory::class)) {
            throw new \InvalidArgumentException(sprintf('a CatchupHandlerFactory of type "%s" already exists in this set', $catchUpHandlerFactory::class), 1650121280);
        }
        $catchUpHandlerFactories = $this->catchUpHandlerFactories;
        $catchUpHandlerFactories[$catchUpHandlerFactory::class] = $catchUpHandlerFactory;
        return new self(...$catchUpHandlerFactories);
    }

    private function has(string $catchUpHandlerFactoryClassName): bool
    {
        return array_key_exists($catchUpHandlerFactoryClassName, $this->catchUpHandlerFactories);
    }

    public function build(): CatchUpHandlerInterface
    {
        $catchUpHandlers = array_map(fn(CatchUpHandlerFactoryInterface $catchUpHandlerFactory) => $catchUpHandlerFactory->build(), $this->catchUpHandlerFactories);
        return new DelegatingCatchUpHandler(...$catchUpHandlers);
    }
}
