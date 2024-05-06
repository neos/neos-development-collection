<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\ContentGraphFactoryInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceStatus;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;

/**
 * @internal only used inside the
 * @see ContentGraphFinder
 */
final readonly class ContentGraphFactory implements ContentGraphFactoryInterface
{
    public function __construct(
        private DbalClientInterface $client,
        private NodeFactory $nodeFactory,
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager,
        private string $tableNamePrefix
    ) {
    }

    public function buildForWorkspace(WorkspaceName $workspaceName): ContentGraph
    {
        // FIXME: Should be part of this projection, this is forbidden
        $tableName = strtolower(sprintf(
            'cr_%s_p_%s',
            $this->contentRepositoryId->value,
            'workspace'
        ));

        $row = $this->client->getConnection()->executeQuery(
            '
                SELECT * FROM ' . $tableName . '
                WHERE workspaceName = :workspaceName
                LIMIT 1
            ',
            [
                'workspaceName' => $workspaceName->value,
            ]
        )->fetchAssociative();

        if ($row === false) {
            throw new ContentStreamDoesNotExistYet('The workspace "' . $workspaceName->value . '" does not exist.', 1714839710);
        }

        return $this->buildForWorkspaceAndContentStream($workspaceName, ContentStreamId::fromString($row['currentcontentstreamid']));
    }

    public function buildForWorkspaceAndContentStream(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraph
    {
        return new ContentGraph($this->client, $this->nodeFactory, $this->contentRepositoryId, $this->nodeTypeManager, $this->tableNamePrefix, $workspaceName, $contentStreamId);
    }
}
