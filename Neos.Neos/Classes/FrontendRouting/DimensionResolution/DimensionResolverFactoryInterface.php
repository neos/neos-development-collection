<?php
declare(strict_types=1);
namespace Neos\Neos\FrontendRouting\DimensionResolution;

use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;

/**
 * API Contract for creating a {@see DimensionResolverInterface} from Settings (usually
 * `Neos.Neos.sites.*.contentDimensions.resolver.factoryClassName` and  `Neos.Neos.sites.*.contentDimensions.resolver.options`).
 *
 * See {@see DimensionResolverInterface} for documentation.
 *
 * @api
 */
interface DimensionResolverFactoryInterface
{
    public function create(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $dimensionResolverOptions): DimensionResolverInterface;
}
