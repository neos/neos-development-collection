<?php
declare(strict_types=1);

namespace Neos\Neos\SiteDetection\Dto;

use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Neos\Domain\ValueObject\SiteIdentifier;
use Neos\Neos\SiteDetection\SiteDetectionMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Accessor for retrieving the currently resolved Site and Content Repository for the current Frontend Request.
 * The resolving happens inside {@see SiteDetectionMiddleware}.
 */
final class SiteDetectionResult
{
    const ROUTINGPARAMETER_SITEIDENTIFIER = 'site';
    const ROUTINGPARAMETER_CONTENTREPOSITORYIDENTIFIER = 'contentrepository';

    private function __construct(
        public readonly SiteIdentifier              $siteIdentifier,
        public readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
    )
    {
    }

    public static function create(
        SiteIdentifier              $siteIdentifier,
        ContentRepositoryIdentifier $contentRepositoryIdentifier,
    ): self
    {
        return new self($siteIdentifier, $contentRepositoryIdentifier);
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

        if ($siteIdentifier === null || $contentRepositoryIdentifier === null) {
            throw new \RuntimeException('Current site and content repository could not be extracted from the Request. SiteDetectionMiddleware must run before calling this method!');
        }
        assert($siteIdentifier instanceof SiteIdentifier);
        assert($contentRepositoryIdentifier instanceof ContentRepositoryIdentifier);

        return new self($siteIdentifier, $contentRepositoryIdentifier);
    }

    public function storeInRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $existingParameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();
        $parameters = $existingParameters
            ->withParameter(self::ROUTINGPARAMETER_SITEIDENTIFIER, $this->siteIdentifier)
            ->withParameter(self::ROUTINGPARAMETER_CONTENTREPOSITORYIDENTIFIER, $this->contentRepositoryIdentifier);
        return $request->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, $parameters);
    }
}
