<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterFactoryInterface;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Factory for the ContentGraphAdapter implementation for doctrine DBAL
 * 
 * @internal
 */
class ContentGraphAdapterFactory implements ContentGraphAdapterFactoryInterface
{
    private NodeFactory $nodeFactory;

    private NodeTypeManager $nodeTypeManager;

    private string $tableNamePrefix;

    private ContentRepositoryId $contentRepositoryId;

    public function __construct(
        private readonly Connection $dbalConnection,
        ProjectionFactoryDependencies $projectionFactoryDependencies
    ) {
        $tableNamePrefix = DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix(
            $projectionFactoryDependencies->contentRepositoryId
        );
        $this->tableNamePrefix = $tableNamePrefix;
        $this->contentRepositoryId = $projectionFactoryDependencies->contentRepositoryId;

        $dimensionSpacePointsRepository = new DimensionSpacePointsRepository($this->dbalConnection, $tableNamePrefix);
        $this->nodeTypeManager = $projectionFactoryDependencies->nodeTypeManager;
        $this->nodeFactory = new NodeFactory(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->nodeTypeManager,
            $projectionFactoryDependencies->propertyConverter,
            $dimensionSpacePointsRepository
        );
    }

    public function create(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphAdapterInterface
    {
        return new ContentGraphAdapter($this->dbalConnection, $this->tableNamePrefix, $this->contentRepositoryId, $this->nodeFactory, $this->nodeTypeManager, $workspaceName, $contentStreamId);
    }

    public function createFromContentStreamId(ContentStreamId $contentStreamId): ContentGraphAdapterInterface
    {
        return new ContentGraphAdapter($this->dbalConnection, $this->tableNamePrefix, $this->contentRepositoryId, $this->nodeFactory, $this->nodeTypeManager, null, $contentStreamId);
    }

    public function createFromWorkspaceName(WorkspaceName $workspaceName): ContentGraphAdapterInterface
    {
        return new ContentGraphAdapter($this->dbalConnection, $this->tableNamePrefix, $this->contentRepositoryId, $this->nodeFactory, $this->nodeTypeManager, $workspaceName, null);
    }
}
