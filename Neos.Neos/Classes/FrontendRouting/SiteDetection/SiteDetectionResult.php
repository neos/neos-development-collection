<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\SiteDetection;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Neos\Domain\Model\SiteIdentifier;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Accessor for retrieving the currently resolved Site and Content Repository for the current Frontend Request.
 * The resolving happens inside {@see SiteDetectionMiddleware}, and for accessing the results, you should use
 * {@see SiteDetectionResult::fromRequest()}.
 *
 * TODO NAMING: CurrentlySelectedSiteAndContentRepository?
 *
 * @Flow\Proxy(false)
 * @api
 */
final class SiteDetectionResult
{
    const ROUTINGPARAMETER_REQUESTURIHOST = 'requestUriHost';
    const ROUTINGPARAMETER_SITEIDENTIFIER = 'siteIdentifier';
    const ROUTINGPARAMETER_CONTENTREPOSITORYIDENTIFIER = 'contentRepositoryIdentifier';

    private function __construct(
        // TODO: RequestUriHost als ValueObject?
        public readonly string $requestUriHost,
        public readonly SiteIdentifier              $siteIdentifier,
        public readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
    )
    {
    }

    public static function create(
        UriInterface $requestUri,
        SiteIdentifier              $siteIdentifier,
        ContentRepositoryIdentifier $contentRepositoryIdentifier
    ): self
    {
        return new self($requestUri->getHost(), $siteIdentifier, $contentRepositoryIdentifier);
    }

    /**
     * Helper to retrieve the previously resolved Site and ContentRepository instance.
     *
     * @param ServerRequestInterface $request
     * @return static
     * @api
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $routeParameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();

        return self::fromRouteParameters($routeParameters);
    }

    public static function fromRouteParameters(RouteParameters $routeParameters): self
    {
        $siteIdentifier = $routeParameters->getValue(self::ROUTINGPARAMETER_SITEIDENTIFIER);
        $contentRepositoryIdentifier = $routeParameters->getValue(self::ROUTINGPARAMETER_CONTENTREPOSITORYIDENTIFIER);
        $requestUriHost = $routeParameters->getValue(self::ROUTINGPARAMETER_REQUESTURIHOST);

        if ($requestUriHost === null || $siteIdentifier === null || $contentRepositoryIdentifier === null) {
            throw new \RuntimeException('Current site and content repository could not be extracted from the Request. SiteDetectionMiddleware must run before calling this method!');
        }
        assert($siteIdentifier instanceof SiteIdentifier);
        assert($contentRepositoryIdentifier instanceof ContentRepositoryIdentifier);

        return new self($requestUriHost, $siteIdentifier, $contentRepositoryIdentifier);
    }

    public function storeInRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $existingParameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();
        $parameters = $existingParameters
            ->withParameter(self::ROUTINGPARAMETER_REQUESTURIHOST, $this->requestUriHost)
            ->withParameter(self::ROUTINGPARAMETER_SITEIDENTIFIER, $this->siteIdentifier)
            ->withParameter(self::ROUTINGPARAMETER_CONTENTREPOSITORYIDENTIFIER, $this->contentRepositoryIdentifier);
        return $request->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, $parameters);
    }
}
