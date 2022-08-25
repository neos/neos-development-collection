<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Service;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;
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

class ContentRepositoryBootstrapper implements ContentRepositoryServiceInterface
{
    private function __construct(
        private readonly ContentRepository $contentRepository,
    ) {
    }

    public static function create(ContentRepository $contentRepository): self
    {
        return new self($contentRepository);
    }

    public function getOrCreateLiveContentStream(): ContentStreamIdentifier
    {
        $liveWorkspace = $this->contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());
        if ($liveWorkspace instanceof Workspace) {
            return $liveWorkspace->getCurrentContentStreamIdentifier();
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

    public function getOrCreateRootNodeAggregate(ContentStreamIdentifier $liveContentStreamIdentifier, NodeTypeName $rootNodeTypeName): NodeAggregateIdentifier
    {
        try {
            return $this->contentRepository->getContentGraph()->findRootNodeAggregateByType(
                $liveContentStreamIdentifier,
                $rootNodeTypeName
            )->getIdentifier();

            // TODO make this case more explicit
        } catch (\Exception $exception) {
            $rootNodeAggregateIdentifier = NodeAggregateIdentifier::create();
            $this->contentRepository->handle(new CreateRootNodeAggregateWithNode(
                $liveContentStreamIdentifier,
                $rootNodeAggregateIdentifier,
                $rootNodeTypeName,
                UserIdentifier::forSystemUser()
            ))->block();
            return $rootNodeAggregateIdentifier;
        }
    }
}
