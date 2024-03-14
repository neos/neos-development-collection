<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\TestSuite\Behavior;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\GherkinTableNodeBasedContentDimensionSource;
use Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource\ContentDimensionSourceFactoryInterface;

class GherkinTableNodeBasedContentDimensionSourceFactory implements ContentDimensionSourceFactoryInterface
{
    public static function registerContentDimensionsForContentRepository(ContentRepositoryId $contentRepositoryId, GherkinTableNodeBasedContentDimensionSource $contentDimensions): void
    {
        file_put_contents(self::cacheFileName($contentRepositoryId), serialize($contentDimensions));
    }

    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentDimensionSourceInterface
    {
        $contentDimensionSource = file_get_contents(self::cacheFileName($contentRepositoryId));
        if ($contentDimensionSource === false) {
            throw new \RuntimeException(sprintf('Content dimension source uninitialized for ContentRepository "%s"', $contentRepositoryId->value));
        }
        return unserialize($contentDimensionSource);
    }

    public static function reset(): void
    {
    }

    private static function cacheFileName(ContentRepositoryId $contentRepositoryId): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'contentDimensionsConfiguration_' . $contentRepositoryId->value . '.json';
    }
}
