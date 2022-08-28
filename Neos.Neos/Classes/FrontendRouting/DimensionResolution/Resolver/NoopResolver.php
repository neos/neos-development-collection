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

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver;

use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\DimensionResolution\RequestToDimensionSpacePointContext;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;

/**
 * Resolver which does not do anything.
 *
 * See {@see DimensionResolverInterface} for detailed documentation.
 *
 * @api
 */
final class NoopResolver implements DimensionResolverInterface
{
    public function fromRequestToDimensionSpacePoint(
        RequestToDimensionSpacePointContext $context
    ): RequestToDimensionSpacePointContext {
        return $context;
    }

    public function fromDimensionSpacePointToUriConstraints(
        DimensionSpacePoint $dimensionSpacePoint,
        SiteNodeName $targetSiteIdentifier,
        UriConstraints $uriConstraints
    ): UriConstraints {
        return $uriConstraints;
    }
}
