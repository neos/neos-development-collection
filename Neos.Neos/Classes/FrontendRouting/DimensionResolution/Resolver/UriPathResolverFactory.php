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

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Neos\Domain\Model\SiteConfiguration;
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

    public function create(
        ContentRepositoryId $contentRepositoryId,
        SiteConfiguration $siteConfiguration,
    ): DimensionResolverInterface {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        return UriPathResolver::create(
            Segments::fromArray($siteConfiguration->contentDimensionResolverOptions['segments'] ?? []),
            Separator::fromString($siteConfiguration->contentDimensionResolverOptions['separator'] ?? '_'),
            $contentRepository->getContentDimensionSource(),
            $siteConfiguration->defaultDimensionSpacePoint,
        );
    }
}
