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

use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverFactoryInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Segments;

/**
 *
 * de/b2b
 * de_b2b
 * de___b2b
 *
 * b2b
 *
 * _b2b
 *
 * /de_b2b/foo
 *
 * URI path segment based dimension value resolver
 */
final class UriPathResolverFactory implements DimensionResolverFactoryInterface
{

    public function create(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $dimensionResolverOptions): DimensionResolverInterface
    {
        return new UriPathResolver(
            Segments::fromArray($dimensionResolverOptions['segments']),
            Separator::fromString($dimensionResolverOptions['separator'])
        );
    }
}
