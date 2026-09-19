<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;

/**
 * @internal builds transformators similar to {@see TransformationFactoryInterface} with the additional perk of
 * making the internal {@see PropertyConverter} available.
 * Custom transformations depending on the property converter are not public api.
 */
interface PropertyConverterAwareTransformationFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository,
        PropertyConverter $propertyConverter,
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface;
}
