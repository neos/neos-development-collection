<?php

namespace Neos\Neos\FrontendRouting\ContentRepositoryHook;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryHookFactoryInterface;
use Neos\Flow\Mvc\Routing\RouterCachingService;

final class RouterCacheHookFactory implements ContentRepositoryHookFactoryInterface
{
    public function __construct(
        protected readonly RouterCachingService $routerCachingService,
    ) {
    }

    public function build(ContentRepository $contentRepository, array $options): RouterCacheHook
    {
        return new RouterCacheHook(
            $contentRepository,
            $this->routerCachingService
        );
    }
}
