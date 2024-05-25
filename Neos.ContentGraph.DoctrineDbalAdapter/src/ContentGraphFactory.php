<?php

declare(strict_types=1);

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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\ContentGraphFactoryInterface;
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
        private Connection $dbal,
        private NodeFactory $nodeFactory,
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager,
        private ContentGraphTableNames $tableNames
    ) {
    }

    public function buildForWorkspace(WorkspaceName $workspaceName): ContentGraph
    {
        $currentContentStreamIdStatement = <<<SQL
            SELECT
                currentcontentstreamid
            FROM
                {$this->tableNames->workspace()}
            WHERE
                workspaceName = :workspaceName
            LIMIT 1
        SQL;
        try {
            $currentContentStreamId = $this->dbal->fetchOne($currentContentStreamIdStatement, [
                'workspaceName' => $workspaceName->value,
            ]);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load workspace content stream id from database: %s', $e->getMessage()), 1716486077, $e);
        }

        if ($currentContentStreamId === false) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }

        return $this->buildForWorkspaceAndContentStream($workspaceName, ContentStreamId::fromString($currentContentStreamId));
    }

    public function buildForWorkspaceAndContentStream(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraph
    {
        return new ContentGraph($this->dbal, $this->nodeFactory, $this->contentRepositoryId, $this->nodeTypeManager, $this->tableNames, $workspaceName, $contentStreamId);
    }
}
