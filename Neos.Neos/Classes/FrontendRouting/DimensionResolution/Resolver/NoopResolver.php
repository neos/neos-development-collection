<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverContext;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 * Resolver which does not do anything.
 *
 * See {@see DimensionResolverInterface} for detailed documentation.
 *
 * @api
 */
final class NoopResolver implements DimensionResolverInterface
{
    public function resolveDimensionSpacePoint(DimensionResolverContext $context): DimensionResolverContext
    {
        return $context;
    }

    public function resolveDimensionUriConstraints(UriConstraints $uriConstraints, DimensionSpacePoint $dimensionSpacePoint, SiteDetectionResult $currentSite): UriConstraints
    {
        return $uriConstraints;
    }
}
