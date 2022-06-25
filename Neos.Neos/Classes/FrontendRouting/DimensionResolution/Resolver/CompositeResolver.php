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
use Webmozart\Assert\Assert;

/**
 */
final class CompositeResolver implements DimensionResolverInterface
{
    private function __construct(
        private readonly array $resolvers
    )
    {
    }

    public static function create(
        DimensionResolverInterface... $contentDimensionResolvers
    ): self {
        return new self($contentDimensionResolvers);
    }

    public function resolveDimensionSpacePoint(DimensionResolverContext $context): DimensionResolverContext
    {
        foreach ($this->resolvers as $resolver) {
            $context = $resolver->resolveDimensionSpacePoint($context);
        }
        return $context;
    }

    public function resolveDimensionUriConstraints(UriConstraints $uriConstraints, DimensionSpacePoint $dimensionSpacePoint): UriConstraints
    {
        // TODO: Reihenfolge umdrehen??
    }
}
