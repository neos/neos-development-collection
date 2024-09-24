<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\ContentGraphFactoryInterface;
use Neos\ContentRepository\Core\ContentGraphFinder;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal only used within
 * @see ContentGraphFinder
 */
final readonly class ContentHyperGraphFactory implements ContentGraphFactoryInterface
{
    public function __construct(
        private Connection $dbal,
        private NodeFactory $nodeFactory,
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager,
        private string $tableNamePrefix,
    ) {
    }

    public function buildForWorkspace(WorkspaceName $workspaceName): ContentGraphInterface
    {
        // FIXME: Should be part of this projection, this is forbidden
        $tableName = strtolower(sprintf(
            'cr_%s_p_%s',
            $this->contentRepositoryId->value,
            'Workspace'
        ));

        $row = $this->dbal->executeQuery(
            '
                SELECT * FROM ' . $tableName . '
                WHERE workspaceName = :workspaceName
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

    public function buildForWorkspaceAndContentStream(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphInterface
    {
        return new ContentHyperGraph($this->dbal, $this->nodeFactory, $this->contentRepositoryId, $this->nodeTypeManager, $this->tableNamePrefix, $workspaceName, $contentStreamId);
    }
}
