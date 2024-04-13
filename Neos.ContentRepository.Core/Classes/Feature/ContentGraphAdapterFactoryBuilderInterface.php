<?php

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphAdapterFactory;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;

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
    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies): ContentGraphAdapterFactory;
}
