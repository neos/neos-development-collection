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

use Neos\ContentRepository\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverFactoryInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Segments;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Separator;

/**
 * Factory for {@see UriPathResolver}.
 *
 * @api
 */
final class UriPathResolverFactory implements DimensionResolverFactoryInterface
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    /**
     * @param array<string,mixed> $dimensionResolverOptions
     */
    public function create(
        ContentRepositoryIdentifier $contentRepositoryIdentifier,
        array $dimensionResolverOptions
    ): DimensionResolverInterface {
        $internals = $this->contentRepositoryRegistry->getService(
            $contentRepositoryIdentifier,
            new AutoUriPathResolverFactoryInternalsFactory()
        );
        return UriPathResolver::create(
            Segments::fromArray($dimensionResolverOptions['segments'] ?? []),
            Separator::fromString($dimensionResolverOptions['separator'] ?? '_'),
            $internals->contentDimensionSource
        );
    }
}
