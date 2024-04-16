<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterFactoryInterface;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
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

    private string $tableNamePrefix;

    public function __construct(
        private readonly Connection $dbalConnection,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        PropertyConverter $propertyConverter
    ) {
        $this->tableNamePrefix = DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix(
            $contentRepositoryId
        );

        $dimensionSpacePointsRepository = new DimensionSpacePointsRepository($this->dbalConnection, $this->tableNamePrefix);
        $this->nodeFactory = new NodeFactory(
            $contentRepositoryId,
            $nodeTypeManager,
            $propertyConverter,
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
