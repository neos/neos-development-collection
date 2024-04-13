<?php
namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A provider for ContentGraphAdapterInterface bound to contentStream/Workspace
 * This is available on the write side of the ContentRepository.
 *
 * @see ContentGraphAdapterInterface
 * @internal
 */
class ContentGraphAdapterProvider
{
    /**
     * @var array<string, ContentGraphAdapterInterface>
     */
    private array $adapterInstances = [];

    public function __construct(
        public readonly ContentGraphAdapterFactoryInterface $contentGraphAdapterFactory
    )
    {
    }

    /**
     * TODO: We should not need this,
     * TODO: after introducing the NodeIdentity we can change usages to
     * TODO: ContentGraphAdapterProvider::resolveContentStreamIdAndGet() and remove this
     *
     * @throws ContentStreamDoesNotExistYet if there is no content stream with the provided id
     * @deprecated
     *
     */
    public function resolveWorkspaceNameAndGet(ContentStreamId $contentStreamId): ContentGraphAdapterInterface
    {
        return $this->contentGraphAdapterFactory->adapterFromContentStreamId($contentStreamId);
    }

    /**
     * @throws WorkspaceDoesNotExist if there is no workspace with the provided name
     * @throws ContentStreamDoesNotExistYet if the provided workspace does not resolve to an existing content stream
     */
    public function resolveContentStreamIdAndGet(WorkspaceName $workspaceName): ContentGraphAdapterInterface
    {
        if (isset($this->adapterInstances[$workspaceName->value])) {
            return $this->adapterInstances[$workspaceName->value];
        }

        return $this->contentGraphAdapterFactory->adapterFromWorkspaceName($workspaceName);
    }

    /**
     * Stateful (dirty) override of the chosen ContentStreamId for a given workspace, it applies within the given closure.
     * Implementations must ensure that requesting the contentStreamId for this workspace will resolve to the given
     * override ContentStreamId and vice versa resolving the WorkspaceName from this ContentStreamId should result in the
     * given WorkspaceName within the closure.
     */
    public function overrideContentStreamId(WorkspaceName $workspaceName, ContentStreamId $contentStreamId, \Closure $fn): void
    {
        $adapter = $this->contentGraphAdapterFactory->bindAdapter($workspaceName, $contentStreamId);
        $replacedAdapter = $this->adapterInstances[$workspaceName->value] ?? null;
        $this->adapterInstances[$workspaceName->value] = $adapter;

        try {
            $fn();
        } finally {
            unset($this->adapterInstances[$workspaceName->value]);
            if ($replacedAdapter) {
                $this->adapterInstances[$workspaceName->value] = $replacedAdapter;
            }
        }
    }
}
