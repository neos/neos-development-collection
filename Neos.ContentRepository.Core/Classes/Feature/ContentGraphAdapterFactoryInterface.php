<?php
namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Factory for ContentGraphAdapters to be used only within the ContentGraphAdapterProvider
 *
 * Projection storage implementation specific factory for a
 * ContentGraphAdapter, as those adapters will likely have specific
 * dependencies.
 *
 * @see ContentGraphAdapterProvider
 */
interface ContentGraphAdapterFactoryInterface
{
    public function bindAdapter(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphAdapterInterface;

    public function adapterFromContentStreamId(ContentStreamId $contentStreamId): ContentGraphAdapterInterface;

    public function adapterFromWorkspaceName(WorkspaceName $workspaceName): ContentGraphAdapterInterface;
}
