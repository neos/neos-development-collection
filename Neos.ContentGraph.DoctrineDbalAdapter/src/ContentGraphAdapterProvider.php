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

    public function resolveWorkspaceNameAndGet(ContentStreamId $contentStreamId): ContentGraphAdapterInterface
    {
        // TODO: Implement resolveWorkspaceNameAndGet() method.
    }

    public function resolveContentStreamIdAndGet(WorkspaceName $workspaceName): ContentGraphAdapterInterface
    {
        // TODO: Implement resolveContentStreamIdAndGet() method.
    }

    public function overrideContentStreamId(WorkspaceName $workspaceName, ContentStreamId $contentStreamId, \Closure $fn): void
    {
        // TODO: Implement overrideContentStreamId() method.
    }

}
