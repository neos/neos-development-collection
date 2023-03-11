<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Neos\Domain\Model\SiteConfiguration;

/**
 * API Contract for creating a {@see DimensionResolverInterface} from Settings (usually
 * `Neos.Neos.sites.*.contentDimensions.resolver.factoryClassName`
 * and `Neos.Neos.sites.*.contentDimensions.resolver.options`).
 *
 * See {@see DimensionResolverInterface} for documentation.
 *
 * @api
 */
interface DimensionResolverFactoryInterface
{
    public function create(
        ContentRepositoryId $contentRepositoryId,
        SiteConfiguration $siteConfiguration,
    ): DimensionResolverInterface;
}
