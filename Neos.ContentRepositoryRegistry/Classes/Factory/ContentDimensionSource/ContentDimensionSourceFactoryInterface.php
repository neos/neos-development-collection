<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;

interface ContentDimensionSourceFactoryInterface
{
    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentDimensionSourcePreset): ContentDimensionSourceInterface;
}
