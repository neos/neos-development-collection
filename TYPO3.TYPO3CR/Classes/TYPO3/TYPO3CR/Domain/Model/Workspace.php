<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Utility\Now;
use TYPO3\TYPO3CR\Domain\Service\NodeServiceInterface;
use TYPO3\TYPO3CR\Exception\WorkspaceException;

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
    const PERSONAL_WORKSPACE_PREFIX = 'user-';

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
    protected $title;

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
     * package (e.g. Neos) which TYPO3CR does not depend on.
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
     * @var \TYPO3\TYPO3CR\Domain\Model\NodeData
     * @ORM\ManyToOne
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $rootNodeData;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Service\PublishingServiceInterface
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
    public function initializeObject($initializationCause)
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the workspace title
     *
     * @return string
     * @api
     */
    public function getTitle()
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
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the workspace description
     *
     * @return string
     * @api
     */
    public function getDescription()
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
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the workspace owner.
     *
     * @return UserInterface
     * @api
     */
    public function getOwner()
    {
        if ($this->owner === null) {
            return null;
        }
        return $this->persistenceManager->getObjectByIdentifier($this->owner, $this->reflectionService->getDefaultImplementationClassNameForInterface(UserInterface::class));
    }

    /**
     * Returns the workspace owner.
     *
     * @param UserInterface|string $user The new user, or user's UUID
     * @api
     */
    public function setOwner($user)
    {
        // Note: We need to do a bit of uuid juggling here, because we can't bind the workspaces Owner to a specific
        // implementation, and creating entity relations via interfaces is not supported by Flow. Since the property
        // mapper will call setOwner() with a string parameter (because the property $owner is string), but developers
        // will want to use objects, we need to support both.
        if ($user === null || $user === '') {
            $this->owner = '';
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
     * @return boolean
     * @api
     */
    public function isPersonalWorkspace()
    {
        return strpos($this->name, static::PERSONAL_WORKSPACE_PREFIX) === 0;
    }

    /**
     * Checks if this workspace is shared only across users with access to internal workspaces, for example "reviewers"
     *
     * @return boolean
     * @api
     */
    public function isPrivateWorkspace()
    {
        return $this->owner !== null && !$this->isPersonalWorkspace();
    }

    /**
     * Checks if this workspace is shared across all editors
     *
     * @return boolean
     * @api
     */
    public function isInternalWorkspace()
    {
        return $this->baseWorkspace !== null && $this->owner === null;
    }

    /**
     * Checks if this workspace is public to everyone, even without authentication
     *
     * @return boolean
     * @api
     */
    public function isPublicWorkspace()
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
    public function setBaseWorkspace(Workspace $baseWorkspace)
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
     * @return Workspace
     * @api
     */
    public function getBaseWorkspace()
    {
        return $this->baseWorkspace;
    }

    /**
     * Returns all base workspaces, if any
     *
     * @return Workspace[]
     */
    public function getBaseWorkspaces()
    {
        $baseWorkspaces = array();
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
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeData
     */
    public function getRootNodeData()
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
    public function publish(Workspace $targetWorkspace)
    {
        $sourceNodes = $this->publishingService->getUnpublishedNodes($this);
        $this->publishNodes($sourceNodes, $targetWorkspace);
    }

    /**
     * Publishes the given nodes to the target workspace.
     *
     * The specified workspace must be a base workspace of this workspace.
     *
     * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
     * @param Workspace $targetWorkspace The workspace to publish to
     * @return void
     * @api
     */
    public function publishNodes(array $nodes, Workspace $targetWorkspace)
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
     * @param NodeInterface $node The node to publish
     * @param Workspace $targetWorkspace The workspace to publish to
     * @return void
     * @api
     */
    public function publishNode(NodeInterface $node, Workspace $targetWorkspace)
    {
        if ($this->baseWorkspace === null) {
            return;
        }
        if ($node->getWorkspace() !== $this) {
            return;
        }
        // Might happen if a node which has been published during an earlier call of publishNode() is attempted to
        // be published again:
        if ($node->getWorkspace() === $targetWorkspace) {
            return;
        }
        $this->verifyPublishingTargetWorkspace($targetWorkspace);
        $this->emitBeforeNodePublishing($node, $targetWorkspace);
        if ($node->getPath() === '/') {
            return;
        }

        $targetNodeData = $this->findNodeDataInTargetWorkspace($node, $targetWorkspace);

        $matchingNodeVariantExistsInTargetWorkspace = $targetNodeData !== null && $targetNodeData->getDimensionValues() === $node->getDimensions();

        if ($matchingNodeVariantExistsInTargetWorkspace) {
            $this->replaceNodeData($node, $targetNodeData);
        } else {
            $this->moveNodeVariantToTargetWorkspace($node, $targetWorkspace);
        }

        $this->emitAfterNodePublishing($node, $targetWorkspace);
    }

    /**
     * Replace the node data of a node instance with a given target node data
     *
     * The node data of the node that is published will be removed and the existing node data inside the target
     * workspace is updated to the changes and will be injected into the node instance. If the node was marked as
     * removed, both node data are removed.
     *
     * @param NodeInterface $node The node instance with node data to be published
     * @param NodeData $targetNodeData The existing node data in the target workspace
     * @return void
     */
    protected function replaceNodeData(NodeInterface $node, NodeData $targetNodeData)
    {
        $sourceNodeData = $node->getNodeData();

        $nodeWasMoved = false;
        $movedShadowNodeData = $this->nodeDataRepository->findOneByMovedTo($sourceNodeData);
        if ($movedShadowNodeData instanceof NodeData) {
            $nodeWasMoved = true;
            if ($movedShadowNodeData->isRemoved()) {
                $this->nodeDataRepository->remove($movedShadowNodeData);
            }
        }

        if ($node->isRemoved() === true) {
            $this->nodeDataRepository->remove($targetNodeData);
        } else {
            $targetNodeData->similarize($node->getNodeData());
            if ($nodeWasMoved) {
                $targetNodeData->setPath($node->getPath(), false);
            }
            $targetNodeData->setLastPublicationDateTime($this->now);
            $node->setNodeData($targetNodeData);
            $this->nodeService->cleanUpProperties($node);
        }
        $this->nodeDataRepository->remove($sourceNodeData);
    }

    /**
     * Move the given node instance to the target workspace
     *
     * If no target node variant (having the same dimension values) exists in the target workspace, the node that
     * is published will be used as a new node variant in the target workspace.
     *
     * @param NodeInterface $node The node to publish
     * @param Workspace $targetWorkspace The workspace to publish to
     * @return void
     */
    protected function moveNodeVariantToTargetWorkspace(NodeInterface $node, Workspace $targetWorkspace)
    {
        $nodeData = $node->getNodeData();

        $movedShadowNodeData = $this->nodeDataRepository->findOneByMovedTo($nodeData);
        if ($movedShadowNodeData instanceof NodeData && $movedShadowNodeData->isRemoved()) {
            $this->nodeDataRepository->remove($movedShadowNodeData);
        }

        if ($targetWorkspace->getBaseWorkspace() === null && $node->isRemoved()) {
            $this->nodeDataRepository->remove($nodeData);
        } else {
            $nodeData->setWorkspace($targetWorkspace);
            $nodeData->setLastPublicationDateTime($this->now);
            $this->nodeService->cleanUpProperties($node);
        }
        $node->setNodeDataIsMatchingContext(null);
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
     * @return integer
     * @api
     */
    public function getNodeCount()
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
    protected function verifyPublishingTargetWorkspace(Workspace $targetWorkspace)
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
     * If no NodeData instance is found, null is returned.
     *
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace
     * @return NodeData
     */
    protected function findNodeDataInTargetWorkspace(NodeInterface $node, Workspace $targetWorkspace)
    {
        $nodeData = $this->nodeDataRepository->findOneByIdentifier($node->getIdentifier(), $targetWorkspace, $node->getDimensions());

        return ($nodeData === null || $nodeData->getWorkspace() === $targetWorkspace) ? $nodeData : null;
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
    protected function emitBaseWorkspaceChanged(Workspace $workspace, Workspace $oldBaseWorkspace = null, Workspace $newBaseWorkspace = null)
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
    protected function emitBeforeNodePublishing(NodeInterface $node, Workspace $targetWorkspace)
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
    protected function emitAfterNodePublishing(NodeInterface $node, Workspace $targetWorkspace)
    {
    }
}
