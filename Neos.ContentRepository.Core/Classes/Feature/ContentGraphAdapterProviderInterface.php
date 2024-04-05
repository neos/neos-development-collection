<?php
namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 *  An implementation detail for the write side of the content repository, providing low level read operations
 *  to facilitate constraint checks and similar.
 *
 *  This needs to be bound to a content repository on creation, so the implementations constructor should
 */
interface ContentGraphAdapterProviderInterface
{
    public function get(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphAdapterInterface;

    public function resolveWorkspaceNameAndGet(ContentStreamId $contentStreamId): ContentGraphAdapterInterface;

    public function resolveContentStreamIdAndGet(WorkspaceName $workspaceName): ContentGraphAdapterInterface;
}
