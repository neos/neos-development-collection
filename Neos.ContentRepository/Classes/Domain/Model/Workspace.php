<?php
namespace Neos\ContentRepository\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Utility\Now;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\NodeServiceInterface;
use Neos\ContentRepository\Domain\Service\PublishingServiceInterface;
use Neos\ContentRepository\Exception\WorkspaceException;

/**
 * A Workspace
 *
 * @Flow\Entity
 * @api
 */
class Workspace
{
    /**
     * This prefix determines if a given workspace (name) is a user workspace.
     */
    public const PERSONAL_WORKSPACE_PREFIX = 'user-';

    /**
     * @var string
     * @Flow\Identity
     * @ORM\Id
     * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=200 })
     */
    protected $name;

    /**
     * A user-defined, human-friendly title for this workspace
     *
     * @var string
     * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=200 })
     */
    protected $title = '';

    /**
     * An optional user-defined description
     *
     * @var string
     * @ORM\Column(type="text", length=500, nullable=true)
     * @Flow\Validate(type="StringLength", options={ "minimum"=0, "maximum"=500 })
     */
    protected $description;

    /**
     * This property contains a UUID of the User object which is the owner of this workspace.
     * We can't use a real many-to-many relation here, because the User implementation will come from a different
     * package (e.g. Neos) which ContentRepository does not depend on.
     *
     * This relation may be implemented with a target entity listener at a later stage, when we implemented support
     * for it in Flow core.
     *
     * See also: http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/cookbook/resolve-target-entity-listener.html
     *
     * @var string
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    protected $owner;

    /**
     * Workspace (if any) this workspace is based on.
     *
     * Content from the base workspace will shine through in this workspace
     * as long as they are not modified in this workspace.
     *
     * @var Workspace
     * @ORM\ManyToOne
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $baseWorkspace;

    /**
     * Root node data of this workspace
     *
     * @var NodeData
     * @ORM\ManyToOne
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $rootNodeData;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var PublishingServiceInterface
     */
    protected $publishingService;

    /**
     * @Flow\Inject
     * @var NodeServiceInterface
     */
    protected $nodeService;

    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Constructs a new workspace
     *
     * @param string $name Name of this workspace
     * @param Workspace $baseWorkspace A workspace this workspace is based on (if any)
     * @param UserInterface $owner The user that created the workspace (if any, "system" workspaces have none)
     * @api
     */
    public function __construct($name, Workspace $baseWorkspace = null, UserInterface $owner = null)
    {
        $this->name = $name;
        $this->title = $name;
        $this->baseWorkspace = $baseWorkspace;
        $this->owner = $owner;
    }

    /**
     * Initializes this workspace.
     *
     * If this workspace is brand new, a root node is created automatically.
     *
     * @param integer $initializationCause
     * @return void
     */
    public function initializeObject($initializationCause): void
    {
        if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
            $this->rootNodeData = new NodeData('/', $this);
            $this->nodeDataRepository->add($this->rootNodeData);

            if ($this->owner instanceof UserInterface) {
                $this->setOwner($this->owner);
            }
        }
    }

    /**
     * Returns the name of this workspace
     *
     * @return string Name of this workspace
     * @api
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the workspace title
     *
     * @return string
     * @api
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets workspace title
     *
     * @param string $title
     * @return void
     * @api
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Returns the workspace description
     *
     * @return string
     * @api
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the workspace description
     *
     * @param string $description
     * @return void
     * @api
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Returns the workspace owner.
     *
     * @return UserInterface|null
     * @api
     */
    public function getOwner(): ?UserInterface
    {
        if ($this->owner === null) {
            return null;
        }
        return $this->persistenceManager->getObjectByIdentifier($this->owner, $this->reflectionService->getDefaultImplementationClassNameForInterface(UserInterface::class));
    }

    /**
     * Returns the workspace owner.
     *
     * @param UserInterface|string|null $user The new user, or user's UUID
     * @api
     */
    public function setOwner($user): void
    {
        // Note: We need to do a bit of uuid juggling here, because we can't bind the workspaces Owner to a specific
        // implementation, and creating entity relations via interfaces is not supported by Flow. Since the property
        // mapper will call setOwner() with a string parameter (because the property $owner is string), but developers
        // will want to use objects, we need to support both.
        if ($user === null || $user === '') {
            $this->owner = null;
            return;
        }
        if (is_string($user) && preg_match('/^([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}$/', $user)) {
            $this->owner = $user;
            return;
        }
        if (!$user instanceof UserInterface) {
            throw new \InvalidArgumentException(sprintf('$user must be an instance of UserInterface, %s given.', gettype($user)), 1447764244);
        }
        $this->owner = $this->persistenceManager->getIdentifierByObject($user);
    }

    /**
     * Checks if this workspace is a user's personal workspace
     *
     * @return bool
     * @api
     */
    public function isPersonalWorkspace(): bool
    {
        return strpos($this->name, static::PERSONAL_WORKSPACE_PREFIX) === 0;
    }

    /**
     * Checks if this workspace is shared only across users with access to internal workspaces, for example "reviewers"
     *
     * @return bool
     * @api
     */
    public function isPrivateWorkspace(): bool
    {
        return $this->owner !== null && !$this->isPersonalWorkspace();
    }

    /**
     * Checks if this workspace is shared across all editors
     *
     * @return bool
     * @api
     */
    public function isInternalWorkspace(): bool
    {
        return $this->baseWorkspace !== null && $this->owner === null;
    }

    /**
     * Checks if this workspace is public to everyone, even without authentication
     *
     * @return bool
     * @api
     */
    public function isPublicWorkspace(): bool
    {
        return $this->baseWorkspace === null && $this->owner === null;
    }

    /**
     * Sets the base workspace
     *
     * Note that this method is not part of the public API because further action is necessary for rebasing a workspace
     *
     * @param Workspace $baseWorkspace
     * @return void
     */
    public function setBaseWorkspace(Workspace $baseWorkspace): void
    {
        $oldBaseWorkspace = $this->baseWorkspace;
        if ($oldBaseWorkspace !== $baseWorkspace) {
            $this->baseWorkspace = $baseWorkspace;
            $this->emitBaseWorkspaceChanged($this, $oldBaseWorkspace, $baseWorkspace);
        }
    }

    /**
     * Returns the base workspace, if any
     *
     * @return Workspace|null
     * @api
     */
    public function getBaseWorkspace(): ?Workspace
    {
        return $this->baseWorkspace;
    }

    /**
     * Returns all base workspaces, if any
     *
     * @return Workspace[]
     */
    public function getBaseWorkspaces(): array
    {
        $baseWorkspaces = [];
        $baseWorkspace = $this->baseWorkspace;

        while ($baseWorkspace !== null) {
            $baseWorkspaces[$baseWorkspace->getName()] = $baseWorkspace;
            $baseWorkspace = $baseWorkspace->getBaseWorkspace();
        }

        return $baseWorkspaces;
    }

    /**
     * Returns the root node data of this workspace
     *
     * @return NodeData
     */
    public function getRootNodeData(): NodeData
    {
        return $this->rootNodeData;
    }

    /**
     * Publishes the content of this workspace to another workspace.
     *
     * The specified workspace must be a base workspace of this workspace.
     *
     * @param Workspace $targetWorkspace The workspace to publish to
     * @return void
     * @api
     */
    public function publish(Workspace $targetWorkspace): void
    {
        $sourceNodes = $this->publishingService->getUnpublishedNodes($this);
        $this->publishNodes($sourceNodes, $targetWorkspace);
    }

    /**
     * Publishes the given nodes to the target workspace.
     *
     * The specified workspace must be a base workspace of this workspace.
     *
     * @param array<\Neos\ContentRepository\Domain\Model\NodeInterface> $nodes
     * @param Workspace $targetWorkspace The workspace to publish to
     * @return void
     * @api
     */
    public function publishNodes(array $nodes, Workspace $targetWorkspace): void
    {
        foreach ($nodes as $node) {
            $this->publishNode($node, $targetWorkspace);
        }
    }

    /**
     * Publishes the given node to the target workspace.
     *
     * The specified workspace must be a base workspace of this workspace.
     *
     * @param NodeInterface $nodeToPublish The node to publish
     * @param Workspace $targetWorkspace The workspace to publish to
     * @return void
     * @api
     */
    public function publishNode(NodeInterface $nodeToPublish, Workspace $targetWorkspace): void
    {
        if ($this->publishNodeCanBeSkipped($nodeToPublish, $targetWorkspace)) {
            return;
        }
        $this->emitBeforeNodePublishing($nodeToPublish, $targetWorkspace);

        $correspondingNodeDataInTargetWorkspace = $this->findCorrespondingNodeDataInTargetWorkspace($nodeToPublish, $targetWorkspace);
        $matchingNodeVariantExistsInTargetWorkspace = ($correspondingNodeDataInTargetWorkspace !== null && $correspondingNodeDataInTargetWorkspace->getDimensionValues() === $nodeToPublish->getDimensions());

        // Save the original node workspace because the workspace of $nodeToPublish can be changed by replaceNodeData():
        $originalNodeWorkspace = $nodeToPublish->getWorkspace();

        if ($matchingNodeVariantExistsInTargetWorkspace) {
            $this->replaceNodeData($nodeToPublish, $correspondingNodeDataInTargetWorkspace);
            $this->moveNodeVariantsInOtherWorkspaces($nodeToPublish->getIdentifier(), $nodeToPublish->getPath(), $originalNodeWorkspace, $targetWorkspace);
        } else {
            $this->moveNodeVariantToTargetWorkspace($nodeToPublish, $targetWorkspace);
        }

        $this->emitAfterNodePublishing($nodeToPublish, $targetWorkspace);
    }

    /**
     * Checks if the given node can / needs to be published to the given target workspace or if that operation can
     * be skipped.
     *
     * @param NodeInterface $node The node to be published
     * @param Workspace $targetWorkspace The target workspace
     * @return bool
     * @throws WorkspaceException
     */
    protected function publishNodeCanBeSkipped(NodeInterface $node, Workspace $targetWorkspace): bool
    {
        if ($this->baseWorkspace === null) {
            return true;
        }
        if ($node->getWorkspace() !== $this) {
            return true;
        }
        // Might happen if a node which has been published during an earlier call of publishNode() is attempted to
        // be published again:
        if ($node->getWorkspace() === $targetWorkspace) {
            return true;
        }
        $this->verifyPublishingTargetWorkspace($targetWorkspace);
        if ($node->getPath() === '/') {
            return true;
        }

        return false;
    }

    /**
     * Replace the node data of a node instance with a given target node data
     *
     * The current node data of $node will be removed and be replaced by $targetNodeData.
     * If $node was marked as removed, both node data instances are removed.
     *
     * @param NodeInterface $sourceNode The node instance with node data to be published
     * @param NodeData $targetNodeData The existing node data in the target workspace
     * @return void
     */
    protected function replaceNodeData(NodeInterface $sourceNode, NodeData $targetNodeData): void
    {
        $sourceNodeData = $sourceNode->getNodeData();

        // The source node is a regular, not moved node and the target node is a moved shadow node
        if (!$sourceNode->getNodeData()->isRemoved() && $sourceNode->getNodeData()->getMovedTo() === null && $targetNodeData->isRemoved() && $targetNodeData->getMovedTo() !== null) {
            $sourceNodeData->move($sourceNodeData->getPath(), $targetNodeData->getWorkspace());
            return;
        }

        if ($sourceNodeData->getParentPath() !== $targetNodeData->getParentPath()) {
            // When $targetNodeData is moved, the NodeData::move() operation may transform it to a shadow node.
            // moveTargetNodeDataToNewPosition() will return the correct (non-shadow) node in any case.
            $targetNodeData = $this->moveTargetNodeDataToNewPosition($targetNodeData, $sourceNode->getPath());
        }

        $this->adjustShadowNodeDataForNodePublishing($sourceNodeData, $targetNodeData->getWorkspace(), $targetNodeData);

        // Technically this shouldn't be needed but due to doctrines behavior we need it.
        if ($sourceNodeData->isRemoved() && $targetNodeData->getWorkspace()->getBaseWorkspace() === null) {
            $this->nodeDataRepository->remove($targetNodeData);
            $this->nodeDataRepository->remove($sourceNodeData);
            return;
        }

        $targetNodeData->similarize($sourceNodeData);
        $targetNodeData->setLastPublicationDateTime($this->now);

        $sourceNode->setNodeData($targetNodeData);
        $this->nodeService->cleanUpProperties($sourceNode);

        // If the source node was "removed", make sure that the new target node data is "removed" as well.
        $targetNodeData->setRemoved($sourceNodeData->isRemoved());

        $this->nodeDataRepository->remove($sourceNodeData);
    }

    /**
     * Moves variants of a given node which exists in other workspaces than source and target workspace.
     *
     * @param string $nodeIdentifier The node which is about to be moved
     * @param string $targetPath The target node path the node is being moved to
     * @param Workspace $sourceWorkspace The workspace the node is currently located
     * @param Workspace $targetWorkspace The workspace the node is being published to
     */
    protected function moveNodeVariantsInOtherWorkspaces($nodeIdentifier, $targetPath, Workspace $sourceWorkspace, Workspace $targetWorkspace): void
    {
        $nodeDataVariants = $this->nodeDataRepository->findByNodeIdentifier($nodeIdentifier);
        /** @var NodeData $nodeDataVariant */
        foreach ($nodeDataVariants as $nodeDataVariant) {
            if (
                $nodeDataVariant->getWorkspace()->getBaseWorkspace() === null ||
                $nodeDataVariant->getPath() === $targetPath ||
                $nodeDataVariant->getWorkspace() === $sourceWorkspace ||
                $nodeDataVariant->getWorkspace() === $targetWorkspace
            ) {
                continue;
            }

            $shadowNodeData = $this->nodeDataRepository->findOneByMovedTo($nodeDataVariant);
            if ($shadowNodeData === null) {
                $nodeDataVariant->setPath($targetPath);
            }
        }
    }

    /**
     * Moves an existing node in a target workspace to the place it should be in after publish,
     * in order to move all children to the new position as well.
     *
     * @param NodeData $targetNodeData The (publish-) target node data to be moved
     * @param string $destinationPath The destination path of the move
     * @return NodeData Either the same object like $targetNodeData, or, if $targetNodeData was transformed into a shadow node, the new target node (see move())
     */
    protected function moveTargetNodeDataToNewPosition(NodeData $targetNodeData, $destinationPath)
    {
        if ($targetNodeData->getWorkspace()->getBaseWorkspace() === null) {
            $targetNodeData->setPath($destinationPath);
            return $targetNodeData;
        }

        return $targetNodeData->move($destinationPath, $targetNodeData->getWorkspace());
    }

    /**
     * Move the given node instance to the target workspace
     *
     * If no target node variant (having the same dimension values) exists in the target workspace, the node that
     * is published will be re-used as a new node variant in the target workspace.
     *
     * @param NodeInterface $nodeToPublish The node to publish
     * @param Workspace $targetWorkspace The workspace to publish to
     * @return void
     */
    protected function moveNodeVariantToTargetWorkspace(NodeInterface $nodeToPublish, Workspace $targetWorkspace): void
    {
        $nodeData = $nodeToPublish->getNodeData();
        $this->adjustShadowNodeDataForNodePublishing($nodeData, $targetWorkspace, $nodeData);

        // Technically this shouldn't be needed but due to doctrines behavior we need it.
        if ($nodeData->isRemoved() && $targetWorkspace->getBaseWorkspace() === null) {
            $this->nodeDataRepository->remove($nodeData);
            return;
        }

        $nodeData->setMovedTo(null);
        $nodeData->setWorkspace($targetWorkspace);
        $nodeData->setLastPublicationDateTime($this->now);
        $nodeToPublish->setNodeDataIsMatchingContext(null);
        $this->nodeService->cleanUpProperties($nodeToPublish);
    }

    /**
     * Adjusts related shadow nodes for a "publish node" operation.
     *
     * This method will look for a shadow node of $sourceNodeData. That shadow node will either be adjusted or,
     * if the target node in the given target workspace is marked as removed, remove it.
     *
     * @param NodeData $sourceNodeData Node Data of the node to publish
     * @param Workspace $targetWorkspace Workspace the node is going to be published to
     * @param NodeData $targetNodeData
     * @return void
     */
    protected function adjustShadowNodeDataForNodePublishing(NodeData $sourceNodeData, Workspace $targetWorkspace, NodeData $targetNodeData): void
    {
        /** @var NodeData $sourceShadowNodeData */
        $sourceShadowNodeData = $this->nodeDataRepository->findOneByMovedTo($sourceNodeData);
        if ($sourceShadowNodeData === null) {
            return;
        }

        // Technically this is not a shadow node
        if ($sourceShadowNodeData->isRemoved() === false) {
            return;
        }

        // There are no shadow nodes to be considered for a top-level base workspace:
        if ($targetWorkspace->getBaseWorkspace() === null) {
            $this->nodeDataRepository->remove($sourceShadowNodeData);
            return;
        }

        $nodeOnSamePathInTargetWorkspace = $this->nodeDataRepository->findOneByPath($sourceShadowNodeData->getPath(), $targetWorkspace, $sourceNodeData->getDimensionValues());
        if ($nodeOnSamePathInTargetWorkspace !== null && $nodeOnSamePathInTargetWorkspace->getWorkspace() === $targetWorkspace) {
            $this->nodeDataRepository->remove($sourceShadowNodeData);
            return;
        }

        $targetWorkspaceBase = $targetWorkspace->getBaseWorkspace();
        $nodeInTargetWorkspaceBase = $this->nodeDataRepository->findOneByIdentifier($sourceNodeData->getIdentifier(), $targetWorkspaceBase, $sourceNodeData->getDimensionValues());
        if ($nodeInTargetWorkspaceBase !== null && $nodeInTargetWorkspaceBase->getPath() === $sourceNodeData->getPath()) {
            $this->nodeDataRepository->remove($sourceShadowNodeData);
            return;
        }

        // From now on $sourceShadowNodeData is published to the target workspace:
        $sourceShadowNodeData->setMovedTo($targetNodeData);
        $sourceShadowNodeData->setWorkspace($targetWorkspace);

        if ($nodeInTargetWorkspaceBase !== null && $nodeInTargetWorkspaceBase->getPath() !== $sourceShadowNodeData->getPath()) {
            $nodeOnSamePathInTargetWorkspace = $this->nodeDataRepository->findOneByPath($nodeInTargetWorkspaceBase->getPath(), $targetWorkspace, $sourceNodeData->getDimensionValues());
            if ($nodeOnSamePathInTargetWorkspace === null || $nodeOnSamePathInTargetWorkspace->getWorkspace() !== $targetWorkspace) {
                $sourceShadowNodeData->setPath($nodeInTargetWorkspaceBase->getPath(), false);
            } else {
                // A node exists in that path, so no shadow node is needed/possible.
                $this->nodeDataRepository->remove($sourceShadowNodeData);
            }
        }

        // Check if a shadow node which has the same path, workspace and dimension values like the shadow node data we just created already exists (in the target workspace).
        // If it does, we re-use the existing node and make sure that all properties etc. are taken from the node which is being published.
        $existingShadowNodeDataInTargetWorkspace = $this->nodeDataRepository->findOneByPath($sourceShadowNodeData->getPath(), $targetWorkspace, $sourceShadowNodeData->getDimensionValues(), true);

        // findOneByPath() might return a node from a different workspace than the $targetWorkspace we specified, so we need to check that, too:
        if ($existingShadowNodeDataInTargetWorkspace !== null && $existingShadowNodeDataInTargetWorkspace->getWorkspace() === $targetWorkspace) {
            $existingShadowNodeDataInTargetWorkspace->similarize($sourceShadowNodeData);
            $existingShadowNodeDataInTargetWorkspace->setMovedTo($sourceShadowNodeData->getMovedTo());
            $existingShadowNodeDataInTargetWorkspace->setRemoved($sourceShadowNodeData->isRemoved());
            $this->nodeDataRepository->remove($sourceShadowNodeData);
        }
    }

    /**
     * Returns the number of nodes in this workspace.
     *
     * If $includeBaseWorkspaces is enabled, also nodes of base workspaces are
     * taken into account. If it is disabled (default) then the number of nodes
     * is the actual number (+1) of changes related to its base workspaces.
     *
     * A node count of 1 means that no changes are pending in this workspace
     * because a workspace always contains at least its Root Node.
     *
     * @return int
     * @api
     */
    public function getNodeCount(): int
    {
        return $this->nodeDataRepository->countByWorkspace($this);
    }

    /**
     * Checks if the specified workspace is a base workspace of this workspace
     * and if not, throws an exception
     *
     * @param Workspace $targetWorkspace The publishing target workspace
     * @return void
     * @throws WorkspaceException if the specified workspace is not a base workspace of this workspace
     */
    protected function verifyPublishingTargetWorkspace(Workspace $targetWorkspace): void
    {
        $baseWorkspace = $this;
        while ($baseWorkspace === null || $targetWorkspace->getName() !== $baseWorkspace->getName()) {
            if ($baseWorkspace === null) {
                throw new WorkspaceException(sprintf('The specified workspace "%s" is not a base workspace of "%s".', $targetWorkspace->getName(), $this->getName()), 1289499117);
            }
            $baseWorkspace = $baseWorkspace->getBaseWorkspace();
        }
    }

    /**
     * Returns the NodeData instance with the given identifier from the target workspace.
     * If no NodeData instance is found in that target workspace, null is returned.
     *
     * @param NodeInterface $node The reference node to find a corresponding variant for
     * @param Workspace $targetWorkspace The target workspace to look in
     * @return NodeData|null Either a regular node, a shadow node or null
     */
    protected function findCorrespondingNodeDataInTargetWorkspace(NodeInterface $node, Workspace $targetWorkspace): ?NodeData
    {
        $nodeData = $this->nodeDataRepository->findOneByIdentifier($node->getIdentifier(), $targetWorkspace, $node->getDimensions(), true);
        if ($nodeData === null || $nodeData->getWorkspace() !== $targetWorkspace) {
            return null;
        }
        return $nodeData;
    }

    /**
     * Emits a signal after the base workspace has been changed
     *
     * @param Workspace $workspace This workspace
     * @param Workspace $oldBaseWorkspace The workspace which was the base workspace before the change
     * @param Workspace $newBaseWorkspace The new base workspace
     * @return void
     * @Flow\Signal
     */
    protected function emitBaseWorkspaceChanged(Workspace $workspace, Workspace $oldBaseWorkspace = null, Workspace $newBaseWorkspace = null): void
    {
    }

    /**
     * Emits a signal just before a node is being published
     *
     * The signal emits the source node and target workspace, i.e. the node contains its source
     * workspace.
     *
     * @param NodeInterface $node The node to be published
     * @param Workspace $targetWorkspace The publishing target workspace
     * @return void
     * @Flow\Signal
     */
    protected function emitBeforeNodePublishing(NodeInterface $node, Workspace $targetWorkspace): void
    {
    }

    /**
     * Emits a signal when a node has been published.
     *
     * The signal emits the source node and target workspace, i.e. the node contains its source
     * workspace.
     *
     * @param NodeInterface $node The node that was published
     * @param Workspace $targetWorkspace The publishing target workspace
     * @return void
     * @Flow\Signal
     */
    protected function emitAfterNodePublishing(NodeInterface $node, Workspace $targetWorkspace): void
    {
    }
}
