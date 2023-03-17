<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource;

use Neos\ContentRepository\Core\Dimension\ConfigurationBasedContentDimensionSource;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

class ConfigurationBasedContentDimensionSourceFactory implements ContentDimensionSourceFactoryInterface
{
    public function __construct(
    )
    {
    }

    public function build(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentDimensionSourcePreset): ContentDimensionSourceInterface
    {
        return new ConfigurationBasedContentDimensionSource(
            $contentRepositorySettings['contentDimensions'] ?? []
        );
    }
}
