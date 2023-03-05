<?php
declare(strict_types=1);
namespace Neos\Neos\Domain\Model;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class SiteConfiguration
{

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

        return new self(
            ContentRepositoryId::fromString($contentRepositoryId),
            $contentDimensionResolverFactoryClassName,
            $contentDimensionResolverOptions,
            $defaultDimensionSpacePoint
        );
    }

    private function __construct(
        public readonly ContentRepositoryId $contentRepositoryId,
        public readonly string $contentDimensionResolverFactoryClassName,
        public readonly array $contentDimensionResolverOptions,
        public readonly DimensionSpacePoint $defaultDimensionSpacePoint,
    ) {
    }
}
