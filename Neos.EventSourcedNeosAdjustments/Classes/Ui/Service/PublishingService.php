<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Intermediary\Domain\ReadModelFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\RebaseWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Changes\Change;
use Neos\EventSourcedContentRepository\Domain\Projection\Changes\ChangeFinder;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedNeosAdjustments\Domain\Service\RuntimeBlocker;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Service\UserService;

/**
 * A generic ContentRepository Publishing Service
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PublishingService
{
    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var ChangeFinder
     */
    protected $changeFinder;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var WorkspaceCommandHandler
     */
    protected $workspaceCommandHandler;

    /**
     * @Flow\Inject
     * @var RuntimeBlocker
     */
    protected $runtimeBlocker;

    /**
     * @Flow\Inject
     * @var ReadModelFactory
     */
    protected $readModelFactory;

    /**
     * Returns a list of nodes contained in the given workspace which are not yet published
     *
     * @param WorkspaceName $workspaceName
     * @return NodeBasedReadModelInterface[]
     * @api
     */
    public function getUnpublishedNodes(WorkspaceName $workspaceName)
    {
        $workspace = $this->workspaceFinder->findOneByName($workspaceName);
        if ($workspace->getBaseWorkspaceName() === null) {
            return [];
        }
        $changes = $this->changeFinder->findByContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());
        $unpublishedNodes = [];
        foreach ($changes as $change) {
            /* @var $change Change */
            $subgraph = $this->contentGraph->getSubgraphByIdentifier(
                $workspace->getCurrentContentStreamIdentifier(),
                $change->originDimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
            $node = $subgraph->findNodeByNodeAggregateIdentifier($change->nodeAggregateIdentifier);

            if ($node instanceof NodeInterface) {
                $unpublishedNodes[] = $this->readModelFactory->createReadModel($node, $subgraph);
            }
        }
        return $unpublishedNodes;
    }

    /**
     * Returns the number of unpublished nodes contained in the given workspace
     *
     * @param Workspace $workspace
     * @return integer
     * @api
     */
    public function getUnpublishedNodesCount(Workspace $workspace)
    {
        $workspace = $this->workspaceFinder->findOneByName(new WorkspaceName($workspace->getName()));
        return $this->changeFinder->countByContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());
    }

    public function publishWorkspace(WorkspaceName $workspaceName): void
    {
        $userIdentifier = UserIdentifier::fromString(
            $this->persistenceManager->getIdentifierByObject($this->userService->getBackendUser())
        );

        // TODO: only rebase if necessary!
        $this->runtimeBlocker->blockUntilProjectionsAreUpToDate(
            $this->workspaceCommandHandler->handleRebaseWorkspace(new RebaseWorkspace(
                $workspaceName,
                $userIdentifier
            ))
        );

        $this->runtimeBlocker->blockUntilProjectionsAreUpToDate(
            $this->workspaceCommandHandler->handlePublishWorkspace(new PublishWorkspace(
                $workspaceName,
                $userIdentifier
            ))
        );
    }
}
