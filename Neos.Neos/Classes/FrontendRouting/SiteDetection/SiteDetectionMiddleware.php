<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\SiteDetection;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\CrossSiteLinking\CrossSiteLinkerInterface;
use Neos\Neos\FrontendRouting\EventSourcedFrontendNodeRoutePartHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This is basically a singleton which globally, at the beginning of the
 * request, decides which Site and Content Repository is active.
 *
 * Is a planned extension point; feel free to override this.
 *
 * When generating links, make sure to also replace the {@see CrossSiteLinkerInterface}.
 *
 * **See {@see EventSourcedFrontendNodeRoutePartHandler} documentation for a
 * detailed explanation of the Frontend Routing process.**
 */
final class SiteDetectionMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestUriHost = $request->getUri()->getHost();
        $site = null;
        if (!empty($requestUriHost)) {
            $activeDomain = $this->domainRepository->findOneByHost($requestUriHost, true);
            if ($activeDomain !== null) {
                $site = $activeDomain->getSite();
            }
        }
        if ($site === null) {
            $site = $this->siteRepository->findFirstOnline();
        }
        assert($site instanceof Site);

        $siteDetectionResult = SiteDetectionResult::create($site->getNodeName(), $site->getConfiguration()->contentRepositoryId);
        return $handler->handle($siteDetectionResult->storeInRequest($request));
    }
}
