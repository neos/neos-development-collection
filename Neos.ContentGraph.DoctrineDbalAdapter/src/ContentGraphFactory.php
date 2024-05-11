<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\ContentGraphFactoryInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

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
        private ContentGraphTableNames $tableNames
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
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }

        return $this->buildForWorkspaceAndContentStream($workspaceName, ContentStreamId::fromString($row['currentcontentstreamid']));
    }

    public function buildForWorkspaceAndContentStream(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraph
    {
        return new ContentGraph($this->client, $this->nodeFactory, $this->contentRepositoryId, $this->nodeTypeManager, $this->tableNames, $workspaceName, $contentStreamId);
    }
}
