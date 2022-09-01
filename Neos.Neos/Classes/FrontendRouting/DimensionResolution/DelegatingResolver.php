<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\Domain\Model\SiteNodeName;
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
    ) {
    }

    public function fromRequestToDimensionSpacePoint(
        RequestToDimensionSpacePointContext $context
    ): RequestToDimensionSpacePointContext {
        $siteDetectionResult = SiteDetectionResult::fromRouteParameters($context->routeParameters);
        $site = $this->siteRepository->findOneByNodeName($siteDetectionResult->siteNodeName);
        if ($site === null) {
            throw new \RuntimeException(
                'Did not find site object for identifier ' . $siteDetectionResult->siteNodeName->value
            );
        }
        $siteConfiguration = $site->getConfiguration();
        $factory = $this->objectManager->get(
            $siteConfiguration['contentDimensions']['resolver']['factoryClassName']
                ?? throw new \RuntimeException(
                    'No Dimension Resolver Factory configured at'
                        . ' Neos.Neos.sites.*.contentDimensions.resolver.factoryClassName'
                )
        );
        assert($factory instanceof DimensionResolverFactoryInterface);
        $resolverOptions = $siteConfiguration['contentDimensions']['resolver']['options'] ?? [];
        $context = $factory->create(
            $siteDetectionResult->contentRepositoryId,
            $resolverOptions
        )->fromRequestToDimensionSpacePoint($context);

        return self::fillWithDefaultDimensionSpacePoint(
            $context,
            $siteConfiguration['contentDimensions']['defaultDimensionSpacePoint'] ?? []
        );
    }

    /**
     * @param RequestToDimensionSpacePointContext $context
     * @param array<string,string> $defaultDimensionSpacePointCoordinates
     * @return RequestToDimensionSpacePointContext
     */
    private static function fillWithDefaultDimensionSpacePoint(
        RequestToDimensionSpacePointContext $context,
        array $defaultDimensionSpacePointCoordinates
    ): RequestToDimensionSpacePointContext {
        foreach ($defaultDimensionSpacePointCoordinates as $dimensionName => $defaultDimensionValue) {
            if (!isset($context->resolvedDimensionSpacePoint->coordinates[$dimensionName])) {
                $context = $context->withAddedDimensionSpacePoint(
                    DimensionSpacePoint::fromArray([$dimensionName => $defaultDimensionValue])
                );
            }
        }
        return $context;
    }

    public function fromDimensionSpacePointToUriConstraints(
        DimensionSpacePoint $dimensionSpacePoint,
        SiteNodeName $targetSiteIdentifier,
        UriConstraints $uriConstraints
    ): UriConstraints {
        $targetSite = $this->siteRepository->findOneByNodeName($targetSiteIdentifier);

        if ($targetSite === null) {
            throw new \RuntimeException('Did not find site object for identifier ' . $targetSiteIdentifier->value);
        }
        $siteConfiguration = $targetSite->getConfiguration();
        $contentRepositoryIdentifier = ContentRepositoryId::fromString(
            $siteConfiguration['contentRepository']
                ?? throw new \RuntimeException(
                    'There is no content repository identifier configured in Sites configuration'
                        . '  in Settings.yaml: Neos.Neos.sites.*.contentRepository'
                )
        );

        $factory = $this->objectManager->get(
            $siteConfiguration['contentDimensions']['resolver']['factoryClassName']
                ?? throw new \RuntimeException(
                    'No Dimension Resolver Factory configured at'
                        . ' Neos.Neos.sites.*.contentDimensions.resolver.factoryClassName'
                )
        );
        assert($factory instanceof DimensionResolverFactoryInterface);
        $resolverOptions = $siteConfiguration['contentDimensions']['resolver']['options'] ?? [];

        return $factory->create(
            $contentRepositoryIdentifier,
            $resolverOptions
        )->fromDimensionSpacePointToUriConstraints(
            $dimensionSpacePoint,
            $targetSiteIdentifier,
            $uriConstraints
        );
    }
}
