<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource;

use Neos\ContentRepository\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;

interface ContentDimensionSourceFactoryInterface
{
    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentDimensionSourcePreset): ContentDimensionSourceInterface;
}
