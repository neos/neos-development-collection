<?php
declare(strict_types=1);
namespace Neos\Neos\FrontendRouting\DimensionResolution;

use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;

interface DimensionResolverFactoryInterface
{
    public function create(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $dimensionResolverOptions): DimensionResolverInterface;
}
