<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\SiteDetection;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Exception\DatabaseException;
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
        $site = null;
        $requestUriHost = $request->getUri()->getHost();
        try {
            if (!empty($requestUriHost)) {
                // try to get site by domain
                $activeDomain = $this->domainRepository->findOneByHost($requestUriHost, true);
                $site = $activeDomain?->getSite();
            }
            if ($site === null) {
                // try to get any site
                $site = $this->siteRepository->findFirstOnline();
            }
        } catch (DatabaseException) {
            // doctrine might have not been migrated yet or no database is connected.
        }

        if (!$site instanceof Site) {
            // no site has been created yet,
            // but we allow other middlewares / routes to work
            return $handler->handle($request);
        }

        // doctrine is running and we could fetch a site. This makes no promise if the content repository is set up.
        $siteDetectionResult = SiteDetectionResult::create($site->getNodeName(), $site->getConfiguration()->contentRepositoryId);
        return $handler->handle($siteDetectionResult->storeInRequest($request));
    }
}
