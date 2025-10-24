<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph;

use Neos\ContentRepository\Core\Factory\SubscriberFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionFactoryInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryContentGraphStructure;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository\InMemoryContentStreamRegistry;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository\InMemoryWorkspaceRegistry;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository\NodeFactory;

/**
 * Use this class as ProjectionFactory in your configuration to construct a content graph
 *
 * @api
 */
final class InMemoryContentGraphProjectionFactory implements ContentGraphProjectionFactoryInterface
{
    public function build(
        SubscriberFactoryDependencies $projectionFactoryDependencies,
    ): InMemoryContentGraphProjection {
        $nodeFactory = new NodeFactory(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->getPropertyConverter(),
        );

        $graphStructure = InMemoryContentGraphStructure::getInstance();
        $workspaceRegistry = InMemoryWorkspaceRegistry::getInstance();
        $contentStreamRegistry = InMemoryContentStreamRegistry::getInstance();

        $contentGraphReadModel = new InMemoryContentGraphReadModelAdapter(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->nodeTypeManager,
            $graphStructure,
            $workspaceRegistry,
            $contentStreamRegistry,
            $nodeFactory,
        );

        return new InMemoryContentGraphProjection(
            $contentGraphReadModel,
            $graphStructure,
            $workspaceRegistry,
            $contentStreamRegistry,
        );
    }
}
