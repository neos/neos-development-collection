<?php
declare(strict_types=1);
namespace Neos\Neos\FrontendRouting\DimensionResolving;

use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\FrontendRouting\Configuration\SiteConfigurationReader;

/**
 * Factory for creating the {@see DimensionResolverInterface} for a single site.
 *
 * @Flow\Scope("singleton")
 */
class DimensionResolverFactoryFactory
{
    /**
     * @Flow\Inject
     * @var SiteConfigurationReader
     */
    protected $siteConfigurationReader;

    public function build(RouteParameters $routeParameters): DimensionResolverInterface
    {
        $siteDetectionResult = SiteDetectionResult::fromRouteParameters($routeParameters);

        // TODO: ROutePartHandler

        $dimensionResolverClassName = $this->siteConfigurationReader->getDimensionResolverClassNameForSite($siteDetectionResult->siteIdentifier);

        // Full class Name!!!!
        return $this->objectManager->get(....);
    }
}
