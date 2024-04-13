<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterFactoryInterface;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 *
 */
class ContentGraphAdapterFactory implements ContentGraphAdapterFactoryInterface
{
    private NodeFactory $nodeFactory;

    private string $tableNamePrefix;

    public function __construct(
        private readonly Connection $dbalConnection,
        ProjectionFactoryDependencies $projectionFactoryDependencies
    ) {
        $tableNamePrefix = DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix(
            $projectionFactoryDependencies->contentRepositoryId
        );
        $this->tableNamePrefix = $tableNamePrefix;

        $dimensionSpacePointsRepository = new DimensionSpacePointsRepository($this->dbalConnection, $tableNamePrefix);
        $this->nodeFactory = new NodeFactory(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->nodeTypeManager,
            $projectionFactoryDependencies->propertyConverter,
            $dimensionSpacePointsRepository
        );
    }

    public function create(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphAdapterInterface
    {
        return new ContentGraphAdapter($this->dbalConnection, $this->tableNamePrefix, $this->nodeFactory, $workspaceName, $contentStreamId);
    }

    public function createFromContentStreamId(ContentStreamId $contentStreamId): ContentGraphAdapterInterface
    {
        return new ContentGraphAdapter($this->dbalConnection, $this->tableNamePrefix, $this->nodeFactory, null, $contentStreamId);
    }

    public function createFromWorkspaceName(WorkspaceName $workspaceName): ContentGraphAdapterInterface
    {
        return new ContentGraphAdapter($this->dbalConnection, $this->tableNamePrefix, $this->nodeFactory, $workspaceName, null);
    }
}
