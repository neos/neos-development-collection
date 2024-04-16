<?php

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * This allows you to combine constructor injection with dependencies from
 * the ProjectionFactoryDependencies to create the ContentGraphAdapterFactory
 * for a specific projection storage implementation.
 *
 * An implementation of this should be configured per content repository via:
 * Neos.ContentRepositoryRegistry.presets.<CR>.contentGraphAdapterFactory.factoryObjectName
 */
interface ContentGraphAdapterFactoryBuilderInterface
{
    public function build(
        ContentRepositoryId $contentRepositoryId,
        NodeTypeManager $nodeTypeManager,
        PropertyConverter $propertyConverter
    ): ContentGraphAdapterFactoryInterface;
}
