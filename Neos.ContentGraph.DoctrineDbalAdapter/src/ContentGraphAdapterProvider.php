<?php
namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterProviderInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api
 */
class ContentGraphAdapterProvider implements ContentGraphAdapterProviderInterface
{
    public function __construct(
        private readonly Connection $dbalConnection,
        private readonly string $tableNamePrefix,
    ) {
    }

    public function get(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphAdapterInterface
    {
        return new ContentGraphAdapter($this->dbalConnection, $this->tableNamePrefix, $contentStreamId, $workspaceName);
    }
}
