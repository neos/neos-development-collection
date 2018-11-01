<?php
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

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\ContextParameters;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\RebaseWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Changes\Change;
use Neos\EventSourcedContentRepository\Domain\Projection\Changes\ChangeFinder;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Exception\WorkspaceException;
use Neos\ContentRepository\Service\Utility\NodePublishingDependencySolver;

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
     * Returns a list of nodes contained in the given workspace which are not yet published
     *
     * @param WorkspaceName $workspaceName
     * @return TraversableNodeInterface[]
     * @api
     */
    public function getUnpublishedNodes(WorkspaceName $workspaceName)
    {
        $workspace = $this->workspaceFinder->findOneByName($workspaceName);
        if ($workspace->getBaseWorkspaceName() === null) {
            return array();
        }
        $changes = $this->changeFinder->findByContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());
        $unpublishedNodes = [];
        foreach ($changes as $change) {
            /* @var $change Change */
            $subgraph = $this->contentGraph->getSubgraphByIdentifier(
                $workspace->getCurrentContentStreamIdentifier(),
                $change->originDimensionSpacePoint
            );
            $node = $subgraph->findNodeByNodeAggregateIdentifier($change->nodeAggregateIdentifier);

            if ($node instanceof NodeInterface) {
                $unpublishedNodes[] = new TraversableNode($node, $subgraph, new ContextParameters(new \DateTimeImmutable(), [], true, true));
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


    /**
     * @Flow\Inject
     * @var WorkspaceCommandHandler
     */
    protected $workspaceCommandHandler;

    /**
     * @param WorkspaceName $workspaceName
     */
    public function publishWorkspace(WorkspaceName $workspaceName)
    {
        $command = new RebaseWorkspace(
            $workspaceName
        );
        $this->workspaceCommandHandler->handleRebaseWorkspace($command);

        $command = new PublishWorkspace(
            $workspaceName
        );
        $this->workspaceCommandHandler->handlePublishWorkspace($command);
    }

}
