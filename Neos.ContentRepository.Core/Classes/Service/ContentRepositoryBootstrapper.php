<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;

/**
 * Utility class that provides functionality to initialize a Content Repository instance (i.e. create the first
 * workspace; and the root node in there.
 *
 * @api
 */
final readonly class ContentRepositoryBootstrapper
{
    private function __construct(
        private ContentRepository $contentRepository,
    ) {
    }

    public static function create(ContentRepository $contentRepository): self
    {
        return new self($contentRepository);
    }

    /**
     * Retrieve the Content Stream ID of the "live" workspace.
     * If the "live" workspace does not exist yet, it will be created
     */
    public function getOrCreateLiveWorkspace(): Workspace
    {
        $liveWorkspaceName = WorkspaceName::forLive();
        $liveWorkspace = $this->contentRepository->getWorkspaceFinder()->findOneByName($liveWorkspaceName);
        if ($liveWorkspace instanceof Workspace) {
            return $liveWorkspace;
        }

        $this->contentRepository->handle(
            CreateRootWorkspace::create(
                $liveWorkspaceName,
                WorkspaceTitle::fromString('Live'),
                WorkspaceDescription::fromString('Public live workspace'),
                ContentStreamId::create()
            )
        );
        $liveWorkspace = $this->contentRepository->getWorkspaceFinder()->findOneByName($liveWorkspaceName);
        if (!$liveWorkspace) {
            throw new \Exception('Live workspace creation failed', 1699002435);
        }

        return $liveWorkspace;
    }

    /**
     * Retrieve the root Node Aggregate ID for the specified $contentStreamId
     * If no root node of the specified $rootNodeTypeName exist, it will be created
     */
    public function getOrCreateRootNodeAggregate(
        Workspace $workspace,
        NodeTypeName $rootNodeTypeName
    ): NodeAggregateId {
        try {
            return $this->contentRepository->getContentGraph($workspace->workspaceName)->findRootNodeAggregateByType(
                $rootNodeTypeName
            )->nodeAggregateId;

            // TODO make this case more explicit
        } catch (\Exception $exception) {
            $rootNodeAggregateId = NodeAggregateId::create();
            $this->contentRepository->handle(CreateRootNodeAggregateWithNode::create(
                $workspace->workspaceName,
                $rootNodeAggregateId,
                $rootNodeTypeName,
            ));
            return $rootNodeAggregateId;
        }
    }
}
