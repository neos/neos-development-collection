<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Service;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;

/**
 * Utility class that provides functionality to initialize a Content Repository instance (i.e. create the first
 * workspace; and the root node in there.
 *
 * @api
 */
final class ContentRepositoryBootstrapper
{
    private function __construct(
        private readonly ContentRepository $contentRepository,
    ) {
    }

    public static function create(ContentRepository $contentRepository): self
    {
        return new self($contentRepository);
    }

    /**
     * Retrieve the Content Stream Identifier of the "live" workspace.
     * If the "live" workspace does not exist yet, it will be created
     */
    public function getOrCreateLiveContentStream(): ContentStreamIdentifier
    {
        $liveWorkspace = $this->contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());
        if ($liveWorkspace instanceof Workspace) {
            return $liveWorkspace->currentContentStreamIdentifier;
        }
        $liveContentStreamIdentifier = ContentStreamIdentifier::create();
        $this->contentRepository->handle(
            new CreateRootWorkspace(
                WorkspaceName::forLive(),
                WorkspaceTitle::fromString('Live'),
                WorkspaceDescription::fromString('Public live workspace'),
                UserIdentifier::forSystemUser(),
                $liveContentStreamIdentifier
            )
        )->block();
        return $liveContentStreamIdentifier;
    }

    /**
     * Retrieve the root Node Aggregate Identifier for the specified $contentStreamIdentifier
     * If no root node of the specified $rootNodeTypeName exist, it will be created
     */
    public function getOrCreateRootNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $rootNodeTypeName
    ): NodeAggregateIdentifier {
        try {
            return $this->contentRepository->getContentGraph()->findRootNodeAggregateByType(
                $contentStreamIdentifier,
                $rootNodeTypeName
            )->nodeAggregateIdentifier;

            // TODO make this case more explicit
        } catch (\Exception $exception) {
            $rootNodeAggregateIdentifier = NodeAggregateIdentifier::create();
            $this->contentRepository->handle(new CreateRootNodeAggregateWithNode(
                $contentStreamIdentifier,
                $rootNodeAggregateIdentifier,
                $rootNodeTypeName,
                UserIdentifier::forSystemUser()
            ))->block();
            return $rootNodeAggregateIdentifier;
        }
    }
}
