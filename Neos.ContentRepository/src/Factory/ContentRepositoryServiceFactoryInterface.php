<?php

namespace Neos\ContentRepository\Factory;

/**
 * @template T of ContentRepositoryServiceInterface
 */
interface ContentRepositoryServiceFactoryInterface
{
    /**
     * @return T
     */
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface;
}
