<?php

namespace Neos\Neos\FrontendRouting\CatchUpHook;

use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\RedirectHandler\NeosAdapter\Service\NodeRedirectService;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Mvc\Routing\RouterCachingService;

final class RouterCacheHookFactory implements CatchUpHookFactoryInterface
{
    public function __construct(
        protected readonly RouterCachingService $routerCachingService,
    ) {
    }

    public function build(ContentRepository $contentRepository): CatchUpHookInterface
    {
        return new RouterCacheHook(
            $contentRepository,
            $this->routerCachingService
        );
    }
}
