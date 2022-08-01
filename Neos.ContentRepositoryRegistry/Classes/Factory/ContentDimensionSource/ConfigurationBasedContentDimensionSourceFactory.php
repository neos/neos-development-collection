<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource;

use Neos\ContentRepository\DimensionSpace\Dimension\ConfigurationBasedContentDimensionSource;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;

class ConfigurationBasedContentDimensionSourceFactory implements ContentDimensionSourceFactoryInterface
{
    public function __construct(
    )
    {
    }

    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentDimensionSourcePreset): ContentDimensionSourceInterface
    {
        return new ConfigurationBasedContentDimensionSource(
            $contentRepositorySettings['dimensions'] ?? []
        );
    }
}
