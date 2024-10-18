<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Factory;

/**
 * @template T of ContentRepositoryServiceInterface
 *
 * @internal
 */
interface ContentRepositoryServiceFactoryInterface
{
    /**
     * @return T
     */
    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): ContentRepositoryServiceInterface;
}
