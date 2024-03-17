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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\FrontendRouting\EventSourcedFrontendNodeRoutePartHandler;
use Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo;
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
    ) {
    }

    public function fromRequestToDimensionSpacePoint(
        RequestToDimensionSpacePointContext $context
    ): RequestToDimensionSpacePointContext {
        $siteDetectionResult = SiteDetectionResult::fromRouteParameters($context->routeParameters);
        $siteConfiguration = $context->resolvedSite->getConfiguration();

        $factory = $this->objectManager->get($siteConfiguration->contentDimensionResolverFactoryClassName);
        assert($factory instanceof DimensionResolverFactoryInterface);
        $context = $factory->create(
            $siteDetectionResult->contentRepositoryId,
            $siteConfiguration
        )->fromRequestToDimensionSpacePoint($context);

        return $context;
    }

    public function fromDimensionSpacePointToUriConstraints(
        DimensionSpacePoint $filteredDimensionSpacePoint,
        DocumentNodeInfo $targetNodeInfo,
        Site $targetSite,
        UriConstraints $uriConstraints,
    ): UriConstraints {
        $targetSiteConfiguration = $targetSite->getConfiguration();

        $factory = $this->objectManager->get(
            $targetSiteConfiguration->contentDimensionResolverFactoryClassName
        );
        assert($factory instanceof DimensionResolverFactoryInterface);

        return $factory->create(
            $targetSiteConfiguration->contentRepositoryId,
            $targetSiteConfiguration
        )->fromDimensionSpacePointToUriConstraints(
            $filteredDimensionSpacePoint,
            $targetNodeInfo,
            $targetSite,
            $uriConstraints,
        );
    }
}
