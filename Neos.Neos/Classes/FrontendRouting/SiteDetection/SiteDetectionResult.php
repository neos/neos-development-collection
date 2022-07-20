<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\SiteDetection;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Neos\Domain\Model\SiteNodeName;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Accessor for retrieving the currently resolved Site and Content Repository for the current Frontend Request.
 * The resolving happens inside {@see SiteDetectionMiddleware}, and for accessing the results, you should use
 * {@see SiteDetectionResult::fromRequest()}.
 *
 * @Flow\Proxy(false)
 * @api
 */
final class SiteDetectionResult
{
    private const ROUTINGPARAMETER_SITEIDENTIFIER = 'siteIdentifier';
    private const ROUTINGPARAMETER_CONTENTREPOSITORYIDENTIFIER = 'contentRepositoryIdentifier';

    private function __construct(
        public readonly SiteNodeName $siteNodeName,
        public readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
    )
    {
    }

    public static function create(
        SiteNodeName $siteIdentifier,
        ContentRepositoryIdentifier $contentRepositoryIdentifier
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
        assert($siteIdentifier instanceof SiteNodeName);
        assert($contentRepositoryIdentifier instanceof ContentRepositoryIdentifier);

        return new self($siteIdentifier, $contentRepositoryIdentifier);
    }

    public function storeInRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $parameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();
        $parameters = self::storeInRouteParameters($parameters);
        return $request->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, $parameters);
    }

    public function storeInRouteParameters(RouteParameters $routeParameters): RouteParameters
    {
        return $routeParameters
            ->withParameter(self::ROUTINGPARAMETER_SITEIDENTIFIER, $this->siteNodeName)
            ->withParameter(self::ROUTINGPARAMETER_CONTENTREPOSITORYIDENTIFIER, $this->contentRepositoryIdentifier);
    }
}
