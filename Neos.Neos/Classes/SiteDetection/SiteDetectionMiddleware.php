<?php
declare(strict_types=1);

namespace Neos\Neos\SiteDetection;

use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\ValueObject\SiteIdentifier;
use Neos\Neos\SiteDetection\Dto\SiteDetectionResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * TODO explain what this class does and how it relates in the routing.
 *
 * basically "singleton" (global functionality)
 * TODO: how to do reverse direction when generating links?
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
        // TODO: should we read from RequestUriHostMiddleware instead?? I guess yes.
        $host = $request->getUri()->getHost();
        $site = null;
        if (!empty($host)) {
            $activeDomain = $this->domainRepository->findOneByHost($host, true);
            if ($activeDomain !== null) {
                $site = $activeDomain->getSite();
            }
        }
        if ($site === null) {
            $site = $this->siteRepository->findFirstOnline();
        }

        $siteIdentifier = SiteIdentifier::fromSite($site);
        $contentRepositoryIdentifier = $this->siteConfigurationReader->getContentRepositoryIdentifierForSite($siteIdentifier);

        $siteDetectionResult = SiteDetectionResult::create($siteIdentifier, $contentRepositoryIdentifier);
        return $handler->handle($siteDetectionResult->storeInRequest($request));
    }
}
