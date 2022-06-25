<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\EventSourcedFrontendNodeRoutePartHandler;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 * Entry Point to the dimension resolution process - called from {@see EventSourcedFrontendNodeRoutePartHandler}.
 * You will not call this class yourself.
 *
 * See {@see DimensionResolverInterface} for documentation.
 *
 * @Flow\Scope("singleton")
 * @internal
 */
final class DelegatingResolver implements DimensionResolverInterface
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly SiteRepository $siteRepository,
    ) {}

    public function resolveDimensionSpacePoint(DimensionResolverContext $context): DimensionResolverContext
    {
        $siteDetectionResult = SiteDetectionResult::fromRouteParameters($context->routeParameters());
        $site = $this->siteRepository->findByIdentifier($siteDetectionResult->siteIdentifier);
        if ($site === null) {
            throw new \RuntimeException('Did not find site object for identifier ' . $siteDetectionResult->siteIdentifier->getValue());
        }
        $siteConfiguration = $site->getConfiguration();
        $factory = $this->objectManager->get($siteConfiguration['dimensionResolver']['factoryClassName'] ?? throw new \RuntimeException('No Dimension Resolver Factory configured at Neos.Neos.sites.*.dimensionResolver.factoryClassName'));
        assert($factory instanceof DimensionResolverFactoryInterface);
        $resolverOptions = $siteConfiguration['dimensionResolver']['options'] ?? [];
        return $factory->create($siteDetectionResult->contentRepositoryIdentifier, $resolverOptions)->resolveDimensionSpacePoint($context);
    }

    public function resolveDimensionUriConstraints(UriConstraints $uriConstraints, DimensionSpacePoint $dimensionSpacePoint, SiteDetectionResult $currentSite): UriConstraints
    {
        // TODO: SiteDetectionResult parameter?? // TODO: Naming: $currentSite !== $siteDetectionResult. What is better?
        $site = $this->siteRepository->findByIdentifier($currentSite->siteIdentifier);

        if ($site === null) {
            throw new \RuntimeException('Did not find site object for identifier ' . $currentSite->siteIdentifier->getValue());
        }
        $siteConfiguration = $site->getConfiguration();
        $factory = $this->objectManager->get($siteConfiguration['dimensionResolver']['factoryClassName'] ?? throw new \RuntimeException('No Dimension Resolver Factory configured at Neos.Neos.sites.*.dimensionResolver.factoryClassName'));
        assert($factory instanceof DimensionResolverFactoryInterface);
        $resolverOptions = $siteConfiguration['dimensionResolver']['options'] ?? [];
        // TODO: should this be current site or the "other" site??
        return $factory->create($currentSite->contentRepositoryIdentifier, $resolverOptions)->resolveDimensionUriConstraints($uriConstraints, $dimensionSpacePoint, $currentSite);
    }
}
