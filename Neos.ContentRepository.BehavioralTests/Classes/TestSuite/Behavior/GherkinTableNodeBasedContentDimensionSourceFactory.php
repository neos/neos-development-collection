<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\TestSuite\Behavior;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\GherkinTableNodeBasedContentDimensionSource;
use Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource\ContentDimensionSourceFactoryInterface;

class GherkinTableNodeBasedContentDimensionSourceFactory implements ContentDimensionSourceFactoryInterface
{
    public static function registerContentDimensionsForContentRepository(ContentRepositoryId $contentRepositoryId, GherkinTableNodeBasedContentDimensionSource $contentDimensions)
    {
        file_put_contents(self::cacheFileName($contentRepositoryId), serialize($contentDimensions));
    }

    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentDimensionSourceInterface
    {
        if (!file_exists(self::cacheFileName($contentRepositoryId))) {
            throw new \DomainException(sprintf('Content dimension source uninitialized for ContentRepository "%s"', $contentRepositoryId->value));
        }
        return unserialize(file_get_contents(self::cacheFileName($contentRepositoryId)), ['allowed_classes' => [GherkinTableNodeBasedContentDimensionSource::class]]);
    }

    public static function reset(): void
    {

    }

    private static function cacheFileName(ContentRepositoryId $contentRepositoryId): string
    {
        return '/tmp/contentDimensionsConfiguration_' . $contentRepositoryId->value . '.json';
    }
}
