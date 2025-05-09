<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Fakes;

use Neos\ContentRepository\Core\Dimension\ContentDimension;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource\ContentDimensionSourceFactoryInterface;

/**
 * Fake factory for testing. Note that the factory MUST be initialised BEFORE the content repository is fetched.
 * Any changes after initialing a cr are lost UNLESS the content repository is rebuild.
 */
class FakeContentDimensionSourceFactory implements ContentDimensionSourceFactoryInterface
{
    private static ?ContentDimensionSourceInterface $contentDimensionSource = null;

    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentDimensionSourceInterface
    {
        if (!self::$contentDimensionSource) {
            throw new \RuntimeException('Content dimension source not initialized.');
        }
        return self::$contentDimensionSource;
    }

    public static function setContentDimensionSource(ContentDimensionSourceInterface $contentDimensionSource): void
    {
        self::$contentDimensionSource = $contentDimensionSource;
    }

    /**
     * Configures a zero-dimensional content repository
     */
    public static function setWithoutDimensions(): void
    {
        self::$contentDimensionSource = new class implements ContentDimensionSourceInterface
        {
            public function getDimension(ContentDimensionId $dimensionId): ?ContentDimension
            {
                return null;
            }
            public function getContentDimensionsOrderedByPriority(): array
            {
                return [];
            }
        };
    }

    public static function reset(): void
    {
        self::$contentDimensionSource = null;
    }
}
