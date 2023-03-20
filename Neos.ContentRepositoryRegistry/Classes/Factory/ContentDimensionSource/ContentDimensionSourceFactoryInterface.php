<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

interface ContentDimensionSourceFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentDimensionSourcePreset): ContentDimensionSourceInterface;
}
