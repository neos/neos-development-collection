<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource;

use Neos\ContentRepository\Core\Dimension\ConfigurationBasedContentDimensionSource;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

class ConfigurationBasedContentDimensionSourceFactory implements ContentDimensionSourceFactoryInterface
{
    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentDimensionSourceInterface
    {
        return new ConfigurationBasedContentDimensionSource(
            $options['contentDimensions'] ?? []
        );
    }
}
