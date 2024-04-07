<?php
namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
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
    /**
     * TODO: We should not need this,
     * TODO: after introducing the NodeIdentity we can change usages to
     * TODO: ContentGraphAdapterProviderInterface::resolveContentStreamIdAndGet() and remove this
     * @deprecated
     *
     * @throws ContentStreamDoesNotExistYet if there is no content stream with the provided id
     */
    public function resolveWorkspaceNameAndGet(ContentStreamId $contentStreamId): ContentGraphAdapterInterface;

    /**
     * @throws WorkspaceDoesNotExist if there is no workspace with the provided name
     * @throws ContentStreamDoesNotExistYet if the provided workspace does not resolve to an existing content stream
     */
    public function resolveContentStreamIdAndGet(WorkspaceName $workspaceName): ContentGraphAdapterInterface;

    /**
     * Stateful (dirty) override of the chosen ContentStreamId for a given workspace, it applies within the given closure.
     * Implementations must ensure that requesting the contentStreamId for this workspace will resolve to the given
     * override ContentStreamId and vice versa resolving the WorkspaceName from this ContentStreamId should result in the
     * given WorkspaceName within the closure.
     */
    public function overrideContentStreamId(WorkspaceName $workspaceName, ContentStreamId $contentStreamId, \Closure $fn): void;
}
