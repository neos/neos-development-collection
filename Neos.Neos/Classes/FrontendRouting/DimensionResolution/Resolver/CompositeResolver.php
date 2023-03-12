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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\DimensionResolution\RequestToDimensionSpacePointContext;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;
use Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo;

/**
 * Helper class implementing a Resolver Chain.
 *
 * See {@see DimensionResolverInterface} for detailed documentation.
 *
 * @api
 */
final class CompositeResolver implements DimensionResolverInterface
{
    /**
     * @param DimensionResolverInterface[] $resolvers
     */
    private function __construct(
        private readonly array $resolvers
    ) {
    }

    public static function create(
        DimensionResolverInterface ...$contentDimensionResolvers
    ): self {
        return new self($contentDimensionResolvers);
    }

    public function fromRequestToDimensionSpacePoint(
        RequestToDimensionSpacePointContext $context
    ): RequestToDimensionSpacePointContext {
        foreach ($this->resolvers as $resolver) {
            assert($resolver instanceof DimensionResolverInterface);
            $context = $resolver->fromRequestToDimensionSpacePoint($context);
        }
        return $context;
    }

    public function fromDimensionSpacePointToUriConstraints(
        DimensionSpacePoint $filteredDimensionSpacePoint,
        DocumentNodeInfo $targetNodeInfo,
        UriConstraints $uriConstraints
    ): UriConstraints {
        foreach (array_reverse($this->resolvers) as $resolver) {
            assert($resolver instanceof DimensionResolverInterface);
            $uriConstraints = $resolver->fromDimensionSpacePointToUriConstraints(
                $filteredDimensionSpacePoint,
                $targetNodeInfo,
                $uriConstraints
            );
        }
        return $uriConstraints;
    }
}
