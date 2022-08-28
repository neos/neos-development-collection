<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource;

use Neos\ContentRepository\Core\Dimension\ConfigurationBasedContentDimensionSource;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryIdentifier;

class ConfigurationBasedContentDimensionSourceFactory implements ContentDimensionSourceFactoryInterface
{
    public function __construct(
    )
    {
    }

    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentDimensionSourcePreset): ContentDimensionSourceInterface
    {
        return new ConfigurationBasedContentDimensionSource(
            $contentRepositorySettings['contentDimensions'] ?? []
        );
    }
}
