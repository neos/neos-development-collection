<?php
declare(strict_types=1);
namespace Neos\Neos\EventSourcedRouting\ContentDimensionResolver;

use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\SiteDetection\Dto\SiteDetectionResult;
use Neos\Neos\SiteDetection\SiteConfigurationReader;

/**
 * Factory for creating the {@see ContentDimensionResolverInterface} for a single site.
 *
 * @Flow\Scope("singleton")
 */
class ContentDimensionResolverFactory
{
    /**
     * @Flow\Inject
     * @var SiteConfigurationReader
     */
    protected $siteConfigurationReader;

    public function build(RouteParameters $routeParameters): ContentDimensionResolverInterface
    {
        $siteDetectionResult = SiteDetectionResult::fromRouteParameters($routeParameters);

        $dimensionResolverClassName = $this->siteConfigurationReader->getDimensionResolverClassNameForSite($siteDetectionResult->siteIdentifier);
        // TODO: Class name fully qualified logic
        $dimensionResolverOptions = $this->siteConfigurationReader->getDimensionResolverOptionsForSite($siteDetectionResult->siteIdentifier);

        return new $dimensionResolverClassName($siteDetectionResult->contentRepositoryIdentifier, $dimensionResolverOptions);
    }
}
