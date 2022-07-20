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
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\DimensionResolution\RequestToDimensionSpacePointContext;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;

/**
 * Helper class implementing a Resolver Chain.
 *
 * See {@see DimensionResolverInterface} for detailed documentation.
 *
 * @api
 */
final class CompositeResolver implements DimensionResolverInterface
{
    private function __construct(
        private readonly array $resolvers
    ) {
    }

    public static function create(
        DimensionResolverInterface...$contentDimensionResolvers
    ): self {
        return new self($contentDimensionResolvers);
    }

    public function fromRequestToDimensionSpacePoint(RequestToDimensionSpacePointContext $context): RequestToDimensionSpacePointContext
    {
        foreach ($this->resolvers as $resolver) {
            assert($resolver instanceof DimensionResolverInterface);
            $context = $resolver->fromRequestToDimensionSpacePoint($context);
        }
        return $context;
    }

    public function fromDimensionSpacePointToUriConstraints(DimensionSpacePoint $dimensionSpacePoint, SiteNodeName $targetSiteIdentifier, UriConstraints $uriConstraints): UriConstraints
    {
        foreach (array_reverse($this->resolvers) as $resolver) {
            assert($resolver instanceof DimensionResolverInterface);
            $uriConstraints = $resolver->fromDimensionSpacePointToUriConstraints($dimensionSpacePoint, $targetSiteIdentifier, $uriConstraints);
        }
        return $uriConstraints;
    }
}
