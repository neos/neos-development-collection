<?php

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryHookFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryHooks;

/**
 * @api for custom framework integrations, not for users of the CR
 */
final class ContentRepositoryHooksFactory
{
    /**
     * @var array<array{factory: ContentRepositoryHookFactoryInterface, options: array<string, mixed>}>
     */
    private array $factories = [];

    /**
     * @param array<string,mixed> $options
     * @api
     */
    public function registerFactory(ContentRepositoryHookFactoryInterface $factory, array $options): void
    {
        $this->factories[] = [
            'factory' => $factory,
            'options' => $options,
        ];
    }

    /**
     * @internal this method is only called by the {@see ContentRepository}, and not by anybody in userland
     */
    public function build(ContentRepository $contentRepository): ContentRepositoryHooks
    {
        $hooks = [];
        foreach ($this->factories as $factoryDefinition) {
            $factory = $factoryDefinition['factory'];
            $options = $factoryDefinition['options'];
            $hooks[] = $factory->build($contentRepository, $options);
        }
        return ContentRepositoryHooks::fromArray($hooks);
    }
}
