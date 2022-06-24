<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\SiteDetection;

use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\Configuration\SiteConfigurationReader;
use Neos\Neos\FrontendRouting\ValueObject\SiteIdentifier;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * TODO explain what this class does and how it relates in the routing.
 *
 * basically "singleton" (global functionality)
 * TODO: how to do reverse direction when generating links?
 *
 * Can be replaced.
 */
class SiteDetectionMiddleware implements MiddlewareInterface
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

    /**
     * @Flow\Inject
     * @var SiteConfigurationReader
     */
    protected $siteConfigurationReader;


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

        $siteIdentifier = SiteIdentifier::fromSite($site);
        $contentRepositoryIdentifier = $this->siteConfigurationReader->getContentRepositoryIdentifierForSite($siteIdentifier);

        $siteDetectionResult = SiteDetectionResult::create($requestUriHost, $siteIdentifier, $contentRepositoryIdentifier);
        return $handler->handle($siteDetectionResult->storeInRequest($request));
    }
}
