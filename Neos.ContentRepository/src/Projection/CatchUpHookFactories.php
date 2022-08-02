<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\ContentRepository;

final class CatchUpHookFactories implements CatchUpHookFactoryInterface
{
    /**
     * @var array<class-string<CatchUpHookFactoryInterface>>
     */
    private array $catchUpHookFactories;

    private function __construct(CatchUpHookFactoryInterface ...$catchUpHookFactories)
    {
        $this->catchUpHookFactories = $catchUpHookFactories;
    }

    public static function create(): self
    {
        return new self();
    }

    public function with(CatchUpHookFactoryInterface $catchUpHookFactory): self
    {
        if ($this->has($catchUpHookFactory::class)) {
            throw new \InvalidArgumentException(sprintf('a CatchUpHookFactory of type "%s" already exists in this set', $catchUpHookFactory::class), 1650121280);
        }
        $catchUpHookFactories = $this->catchUpHookFactories;
        $catchUpHookFactories[$catchUpHookFactory::class] = $catchUpHookFactory;
        return new self(...$catchUpHookFactories);
    }

    private function has(string $catchUpHookFactoryClassName): bool
    {
        return array_key_exists($catchUpHookFactoryClassName, $this->catchUpHookFactories);
    }

    public function build(ContentRepository $contentRepository): CatchUpHookInterface
    {
        $catchUpHooks = array_map(fn(CatchUpHookFactoryInterface $catchUpHookFactory) => $catchUpHookFactory->build($contentRepository), $this->catchUpHookFactories);
        return new DelegatingCatchUpHook(...$catchUpHooks);
    }
}
