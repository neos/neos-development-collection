<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final readonly class SiteConfiguration
{
    /**
     * @param ContentRepositoryId $contentRepositoryId
     * @param string $contentDimensionResolverFactoryClassName
     * @param array<string,mixed> $contentDimensionResolverOptions
     * @param DimensionSpacePoint $defaultDimensionSpacePoint
     */
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public string $contentDimensionResolverFactoryClassName,
        public array $contentDimensionResolverOptions,
        public DimensionSpacePoint $defaultDimensionSpacePoint,
        public string $uriPathSuffix,
    ) {
    }

    /**
     * @param array<string,mixed> $configuration
     * @return static
     */
    public static function fromArray(array $configuration): self
    {

        $contentRepositoryId = $configuration['contentRepository'] ?? throw new \RuntimeException(
            'There is no content repository identifier configured in Sites configuration in Settings.yaml:'
            . ' Neos.Neos.sites.*.contentRepository'
        );

        $contentDimensionResolverFactoryClassName = $configuration['contentDimensions']['resolver']['factoryClassName'] ?? throw new \RuntimeException(
            'No Dimension Resolver Factory configured at'
            . ' Neos.Neos.sites.*.contentDimensions.resolver.factoryClassName'
        );
        $contentDimensionResolverOptions = $configuration['contentDimensions']['resolver']['options'] ?? [];

        $defaultDimensionSpacePoint = DimensionSpacePoint::fromArray($configuration['contentDimensions']['defaultDimensionSpacePoint'] ?? []);

        $uriPathSuffix = $configuration['uriPathSuffix'] ?? '';

        return new self(
            ContentRepositoryId::fromString($contentRepositoryId),
            $contentDimensionResolverFactoryClassName,
            $contentDimensionResolverOptions,
            $defaultDimensionSpacePoint,
            $uriPathSuffix,
        );
    }
}
