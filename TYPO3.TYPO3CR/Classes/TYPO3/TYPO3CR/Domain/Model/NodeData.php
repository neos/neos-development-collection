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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Exception\NodeExistsException;

/**
 * The node data inside the content repository. This is only a data
 * container that could be exchanged in the future.
 *
 * NOTE: This is internal only and should not be used or extended by userland code.
 *
 * @Flow\Entity
 * @ORM\Table(
 * 	uniqueConstraints={
 * 		@ORM\UniqueConstraint(name="path_workspace_dimensions",columns={"pathhash", "workspace", "dimensionshash"}),
 * 		@ORM\UniqueConstraint(name="identifier_workspace_dimensions_movedto",columns={"identifier", "workspace", "dimensionshash", "movedto"})
 * 	},
 * 	indexes={
 * 		@ORM\Index(name="parentpath_sortingindex",columns={"parentpathhash", "sortingindex"}),
 * 		@ORM\Index(name="identifierindex",columns={"identifier"}),
 * 		@ORM\Index(name="nodetypeindex",columns={"nodetype"})
 * 	}
 * )
 */
class NodeData extends AbstractNodeData
{
    /**
     * Auto-incrementing version of this node data, used for optimistic locking
     *
     * @ORM\Version
     * @var integer
     */
    protected $version;

    /**
     * MD5 hash of the path
     * This property is needed for the unique index over path & workspace (for which the path property is too large).
     * The hash is generated in calculatePathHash().
     *
     * @var string
     * @ORM\Column(length=32)
     */
    protected $pathHash;

    /**
     * Absolute path of this node
     *
     * @var string
     * @ORM\Column(length=4000)
     * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=4000 })
     */
    protected $path;

    /**
     * MD5 hash of the parent path
     * This property is needed to speed up lookup by parent path.
     * The hash is generated in calculateParentPathHash().
     *
     * @var string
     * @ORM\Column(length=32)
     */
    protected $parentPathHash;

    /**
     * Absolute path of the parent path
     *
     * @var string
     * @ORM\Column(length=4000)
     * @Flow\Validate(type="StringLength", options={ "maximum"=4000 })
     */
    protected $parentPath;

    /**
     * Workspace this node is contained in
     *
     * @var \TYPO3\TYPO3CR\Domain\Model\Workspace
     *
     * Note: Since we compare workspace instances for various purposes it's unsafe to have a lazy relation
     * @ORM\ManyToOne
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $workspace;

    /**
     * Identifier of this node which is unique within its workspace
     *
     * @var string
     */
    protected $identifier;

    /**
     * Index within the nodes with the same parent
     *
     * @var integer
     * @ORM\Column(name="sortingindex", nullable=true)
     */
    protected $index;

    /**
     * Level number within the global node tree
     *
     * @var integer
     * @Flow\Transient
     */
    protected $depth;

    /**
     * Node name, derived from its node path
     *
     * @var string
     * @Flow\Transient
     */
    protected $name;

    /**
     * Optional proxy for a content object which acts as an alternative property container
     *
     * @var \TYPO3\TYPO3CR\Domain\Model\ContentObjectProxy
     * @ORM\ManyToOne
     */
    protected $contentObjectProxy;

    /**
     * If this is a removed node. This flag can and is only used in workspaces
     * which do have a base workspace. In a bottom level workspace nodes are
     * really removed, in other workspaces, removal is realized by this flag.
     *
     * @var boolean
     */
    protected $removed = false;

    /**
     * @ORM\OneToMany(mappedBy="nodeData", orphanRemoval=true)
     * @var \Doctrine\Common\Collections\Collection<\TYPO3\TYPO3CR\Domain\Model\NodeDimension>
     */
    protected $dimensions;

    /**
     * MD5 hash of the content dimensions
     * The hash is generated in buildDimensionValues().
     *
     * @var string
     * @ORM\Column(length=32)
     */
    protected $dimensionsHash;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $hiddenBeforeDateTime;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $hiddenAfterDateTime;

    /**
     * @ORM\Column(type="objectarray")
     * @var array
     */
    protected $dimensionValues;

    /**
     * If a node data is moved a "shadow" node data is inserted that references the new node data
     *
     * @var NodeData
     * @ORM\ManyToOne
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $movedTo;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Security\Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Constructs this node data container
     *
     * Creating new nodes by instantiating NodeData is not part of the public API!
     * The content repository needs to properly integrate new nodes into the node
     * tree and therefore you must use createNode() or createNodeFromTemplate()
     * in a Node object which will internally create a NodeData object.
     *
     * @param string $path Absolute path of this node
     * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The workspace this node will be contained in
     * @param string $identifier The node identifier (not the persistence object identifier!). Specifying this only makes sense while creating corresponding nodes
     * @param array $dimensions An array of dimension name to dimension values
     */
    public function __construct($path, Workspace $workspace, $identifier = null, array $dimensions = null)
    {
        $this->setPath($path, false);
        $this->workspace = $workspace;
        $this->identifier = ($identifier === null) ? Algorithms::generateUUID() : $identifier;

        $this->dimensions = new ArrayCollection();
        if ($dimensions !== null) {
            foreach ($dimensions as $dimensionName => $dimensionValues) {
                foreach ($dimensionValues as $dimensionValue) {
                    $this->dimensions->add(new NodeDimension($this, $dimensionName, $dimensionValue));
                }
            }
        }
        $this->buildDimensionValues();
    }

    /**
     * Returns the name of this node
     *
     * @return string
     */
    public function getName()
    {
        return $this->path === '/' ? '' : substr($this->path, strrpos($this->path, '/') + 1);
    }

    /**
     * Sets the absolute path of this node
     *
     * @param string $path
     * @param boolean $recursive
     * @return void
     * @throws \InvalidArgumentException if the given node path is invalid.
     */
    public function setPath($path, $recursive = true)
    {
        if (!is_string($path) || preg_match(NodeInterface::MATCH_PATTERN_PATH, $path) !== 1) {
            throw new \InvalidArgumentException('Invalid path "' . $path . '" (a path must be a valid string, be absolute (starting with a slash) and contain only the allowed characters).', 1284369857);
        }

        if ($path === $this->path) {
            return;
        }

        if ($recursive === true) {
            /** @var $childNodeData NodeData */
            foreach ($this->getChildNodeData() as $childNodeData) {
                $childNodeData->setPath($path . '/' . $childNodeData->getName());
            }
        }

        $pathBeforeChange = $this->path;

        $this->path = $path;
        $this->calculatePathHash();
        if ($path === '/') {
            $this->parentPath = '';
            $this->depth = 0;
        } elseif (substr_count($path, '/') === 1) {
            $this->parentPath = '/';
        } else {
            $this->parentPath = substr($path, 0, strrpos($path, '/'));
        }
        $this->calculateParentPathHash();

        if ($pathBeforeChange !== null) {
            // this method is called both for changing the path AND in the constructor of Node; so we only want to do
            // these things below if called OUTSIDE a constructor.
            $this->emitNodePathChanged($this);
            $this->addOrUpdate();
        }
    }

    /**
     * Returns the path of this node
     *
     * Example: /sites/mysitecom/homepage/about
     *
     * @return string The absolute node path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the absolute path of this node with additional context information (such as the workspace name).
     *
     * Example: /sites/mysitecom/homepage/about@user-admin
     *
     * @return string Node path with context information
     */
    public function getContextPath()
    {
        $contextPath = $this->path;
        $workspaceName = $this->workspace->getName();
        if ($workspaceName !== 'live') {
            $contextPath .= '@' . $workspaceName;
        }
        return $contextPath;
    }

    /**
     * Returns the level at which this node is located.
     * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
     *
     * @return integer
     */
    public function getDepth()
    {
        if ($this->depth === null) {
            $this->depth = $this->path === '/' ? 0 : substr_count($this->path, '/');
        }
        return $this->depth;
    }

    /**
     * Sets the workspace of this node.
     *
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
     * @return void
     */
    public function setWorkspace(Workspace $workspace = null)
    {
        if ($this->workspace !== $workspace) {
            $this->workspace = $workspace;
            $this->addOrUpdate();
        }
    }

    /**
     * Returns the workspace this node is contained in
     *
     * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * Returns the identifier of this node.
     *
     * This UUID is not the same as the technical persistence identifier used by
     * Flow's persistence framework. It is an additional identifier which is unique
     * within the same workspace and is used for tracking the same node in across
     * workspaces.
     *
     * It is okay and recommended to use this identifier for synchronisation purposes
     * as it does not change even if all of the nodes content or its path changes.
     *
     * @return string the node's UUID
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the index of this node
     *
     * @param integer $index The new index
     * @return void
     */
    public function setIndex($index)
    {
        if ($this->index !== $index) {
            $this->index = $index;
            $this->addOrUpdate();
        }
    }

    /**
     * Returns the index of this node which determines the order among siblings
     * with the same parent node.
     *
     * @return integer
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Returns the parent node of this node
     *
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeData The parent node or NULL if this is the root node
     */
    public function getParent()
    {
        if ($this->path === '/') {
            return null;
        }
        return $this->nodeDataRepository->findOneByPath($this->parentPath, $this->workspace);
    }

    /**
     * Returns the parent node path
     *
     * @return string Absolute node path of the parent node
     */
    public function getParentPath()
    {
        return $this->parentPath;
    }

    /**
     * Creates, adds and returns a child node of this node. Also sets default
     * properties and creates default subnodes.
     *
     * @param string $name Name of the new node
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType Node type of the new node (optional)
     * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
     * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
     * @param array $dimensions
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeData
     */
    public function createNodeData($name, NodeType $nodeType = null, $identifier = null, Workspace $workspace = null, array $dimensions = null)
    {
        $newNodeData = $this->createSingleNodeData($name, $nodeType, $identifier, $workspace, $dimensions);
        if ($nodeType !== null) {
            foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
                if (substr($propertyName, 0, 1) === '_') {
                    ObjectAccess::setProperty($newNodeData, substr($propertyName, 1), $propertyValue);
                } else {
                    $newNodeData->setProperty($propertyName, $propertyValue);
                }
            }

            foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeType) {
                $newNodeData->createNodeData($childNodeName, $childNodeType, null, $workspace, $dimensions);
            }
        }
        return $newNodeData;
    }

    /**
     * Creates, adds and returns a child node of this node, without setting default
     * properties or creating subnodes.
     *
     * @param string $name Name of the new node
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType Node type of the new node (optional)
     * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
     * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
     * @param array $dimensions An array of dimension name to dimension values
     * @throws NodeExistsException if a node with this path already exists.
     * @throws \InvalidArgumentException if the node name is not accepted.
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeData
     */
    public function createSingleNodeData($name, NodeType $nodeType = null, $identifier = null, Workspace $workspace = null, array $dimensions = null)
    {
        if (!is_string($name) || preg_match(NodeInterface::MATCH_PATTERN_NAME, $name) !== 1) {
            throw new \InvalidArgumentException('Invalid node name "' . $name . '" (a node name must only contain characters, numbers and the "-" sign).', 1292428697);
        }

        $nodeWorkspace = $workspace ? : $this->workspace;
        $newPath = $this->path . ($this->path === '/' ? '' : '/') . $name;
        if ($this->nodeDataRepository->findOneByPath($newPath, $nodeWorkspace, $dimensions) !== null) {
            throw new NodeExistsException(sprintf('Node with path "' . $newPath . '" already exists in workspace %s and given dimensions %s.', $nodeWorkspace->getName(), var_export($dimensions, true)), 1292503471);
        }

        $newNodeData = new NodeData($newPath, $nodeWorkspace, $identifier, $dimensions);
        if ($nodeType !== null) {
            $newNodeData->setNodeType($nodeType);
        }
        $this->nodeDataRepository->setNewIndex($newNodeData, NodeDataRepository::POSITION_LAST);

        return $newNodeData;
    }

    /**
     * Creates and persists a node from the given $nodeTemplate as child node
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeTemplate $nodeTemplate
     * @param string $nodeName name of the new node. If not specified the name of the nodeTemplate will be used.
     * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
     * @param array $dimensions
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeData the freshly generated node
     */
    public function createNodeDataFromTemplate(NodeTemplate $nodeTemplate, $nodeName = null, Workspace $workspace = null, array $dimensions = null)
    {
        $newNodeName = $nodeName !== null ? $nodeName : $nodeTemplate->getName();

        $possibleNodeName = $newNodeName;
        $counter = 1;
        while ($this->getNode($possibleNodeName) !== null) {
            $possibleNodeName = $newNodeName . '-' . $counter++;
        }

        $newNodeData = $this->createNodeData($possibleNodeName, $nodeTemplate->getNodeType(), $nodeTemplate->getIdentifier(), $workspace, $dimensions);
        $newNodeData->similarize($nodeTemplate);
        return $newNodeData;
    }

    /**
     * Returns a node specified by the given relative path.
     *
     * @param string $path Path specifying the node, relative to this node
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeData The specified node or NULL if no such node exists
     *
     * @TODO This method should be removed, since it doesn't take the context into account correctly
     */
    public function getNode($path)
    {
        return $this->nodeDataRepository->findOneByPath($this->normalizePath($path), $this->workspace);
    }

    /**
     * Change the identifier of this node data
     *
     * NOTE: This is only used for some very rare cases (to replace existing instances when moving).
     *
     * @param string $identifier
     * @return void
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Returns all direct child node data of this node data without reducing the result (multiple variants can be returned)
     *
     * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeData>
     */
    protected function getChildNodeData()
    {
        return $this->nodeDataRepository->findByParentWithoutReduce($this->path, $this->workspace);
    }

    /**
     * Returns the number of child nodes a similar getChildNodes() call would return.
     *
     * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
     * @param Workspace $workspace
     * @param array $dimensions
     * @return integer The number of child nodes
     */
    public function getNumberOfChildNodes($nodeTypeFilter = null, Workspace $workspace, array $dimensions)
    {
        return $this->nodeDataRepository->countByParentAndNodeType($this->path, $nodeTypeFilter, $workspace, $dimensions);
    }

    /**
     * Removes this node and all its child nodes.
     *
     * @return void
     */
    public function remove()
    {
        if ($this->workspace->getBaseWorkspace() === null) {
            $this->nodeDataRepository->remove($this);
        } else {
            $this->removed = true;
        }
    }

    /**
     * Enables using the remove method when only setters are available
     *
     * @param boolean $removed If TRUE, this node and it's child nodes will be removed. Cannot handle FALSE (yet).
     * @return void
     */
    public function setRemoved($removed)
    {
        if ((boolean)$removed === true) {
            $this->removed = true;
            $this->remove();
        } else {
            $this->removed = false;
        }
        $this->addOrUpdate();
    }

    /**
     * If this node is a removed node.
     *
     * @return boolean
     */
    public function isRemoved()
    {
        return $this->removed;
    }

    /**
     * Tells if this node is "visible".
     *
     * For this the "hidden" flag and the "hiddenBeforeDateTime" and "hiddenAfterDateTime" dates are taken into account.
     * The fact that a node is "visible" does not imply that it can / may be shown to the user. Further modifiers
     * such as isAccessible() need to be evaluated.
     *
     * @return boolean
     */
    public function isVisible()
    {
        if ($this->hidden === true) {
            return false;
        }

        return true;
    }

    /**
     * Tells if this node may be accessed according to the current security context.
     *
     * @return boolean
     */
    public function isAccessible()
    {
        if ($this->hasAccessRestrictions() === false) {
            return true;
        }

        if ($this->securityContext->canBeInitialized() === false) {
            return true;
        }

        foreach ($this->accessRoles as $roleName) {
            if ($this->securityContext->hasRole($roleName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tells if a node, in general, has access restrictions, independent of the
     * current security context.
     *
     * @return boolean
     */
    public function hasAccessRestrictions()
    {
        if (!is_array($this->accessRoles) || empty($this->accessRoles)) {
            return false;
        }
        if (count($this->accessRoles) === 1 && in_array('TYPO3.Flow:Everybody', $this->accessRoles)) {
            return false;
        }
        return true;
    }

    /**
     * Internal use, do not retrieve collection directly
     *
     * @return array<NodeDimension>
     */
    public function getDimensions()
    {
        return $this->dimensions->toArray();
    }

    /**
     * Internal use, do not manipulate collection directly
     *
     * @param array<NodeDimension> $dimensionsToBeSet
     * @return void
     */
    public function setDimensions(array $dimensionsToBeSet)
    {
        if ($this->dimensions->count() > 0) {
            $givenDimensions = $dimensionsToBeSet;
            $dimensionsToBeSet = array();
            /** @var NodeDimension $dimensionToBeSet */
            foreach ($givenDimensions as $dimensionToBeSet) {
                $dimensionExisted = false;
                /** @var NodeDimension $dimension */
                foreach ($this->dimensions as $dimension) {
                    if ($dimension->getName() === $dimensionToBeSet->getName() && $dimensionToBeSet->getValue() === $dimension->getValue()) {
                        $dimensionsToBeSet[] = $dimension;
                        $dimensionExisted = true;
                    }
                }

                if (!$dimensionExisted) {
                    $dimensionToBeSet->setNodeData($this);
                    $dimensionsToBeSet[] = $dimensionToBeSet;
                }
            }
        }

        $this->dimensions = new ArrayCollection($dimensionsToBeSet);
        $this->buildDimensionValues();
    }

    /**
     * @return NodeData
     */
    public function getMovedTo()
    {
        return $this->movedTo;
    }

    /**
     * @param NodeData $nodeData
     * @return void
     */
    public function setMovedTo(NodeData $nodeData = null)
    {
        $this->movedTo = $nodeData;
    }

    /**
     * Make the node "similar" to the given source node. That means,
     *  - all properties
     *  - index
     *  - node type
     *  - content object
     * will be set to the same values as in the source node.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\AbstractNodeData $sourceNode
     * @return void
     */
    public function similarize(AbstractNodeData $sourceNode)
    {
        $this->properties = array();
        foreach ($sourceNode->getProperties(true) as $propertyName => $propertyValue) {
            $this->setProperty($propertyName, $propertyValue);
        }

        $propertyNames = array(
            'nodeType', 'hidden', 'hiddenAfterDateTime',
            'hiddenBeforeDateTime', 'hiddenInIndex', 'accessRoles'
        );
        if ($sourceNode instanceof NodeData) {
            $propertyNames[] = 'index';
        }
        foreach ($propertyNames as $propertyName) {
            ObjectAccess::setProperty($this, $propertyName, ObjectAccess::getProperty($sourceNode, $propertyName));
        }

        $contentObject = $sourceNode->getContentObject();
        if ($contentObject !== null) {
            $this->setContentObject($contentObject);
        }
    }

    /**
     * Normalizes the given path and returns an absolute path
     *
     * @param string $path The non-normalized path
     * @return string The normalized absolute path
     * @throws \InvalidArgumentException if your node path contains two consecutive slashes.
     */
    public function normalizePath($path)
    {
        if ($path === '.') {
            return $this->path;
        }

        if (!is_string($path)) {
            throw new \InvalidArgumentException(sprintf('An invalid node path was specified: is of type %s but a string is expected.', gettype($path)), 1357832901);
        }

        if (strpos($path, '//') !== false) {
            throw new \InvalidArgumentException('Paths must not contain two consecutive slashes.', 1291371910);
        }

        if ($path[0] === '/') {
            $absolutePath = $path;
        } else {
            $absolutePath = ($this->path === '/' ? '' : $this->path) . '/' . $path;
        }
        $pathSegments = explode('/', $absolutePath);
        while (each($pathSegments)) {
            if (current($pathSegments) === '..') {
                prev($pathSegments);
                unset($pathSegments[key($pathSegments)]);
                unset($pathSegments[key($pathSegments)]);
                prev($pathSegments);
            } elseif (current($pathSegments) === '.') {
                unset($pathSegments[key($pathSegments)]);
                prev($pathSegments);
            }
        }
        $normalizedPath = implode('/', $pathSegments);
        return ($normalizedPath === '') ? '/' : $normalizedPath;
    }

    /**
     * Returns the dimensions and their values.
     *
     * @return array
     */
    public function getDimensionValues()
    {
        return $this->dimensionValues;
    }

    /**
     * Build a cached array of dimension values and a hash to search for it.
     *
     * @return void
     */
    protected function buildDimensionValues()
    {
        $dimensionValues = array();
        foreach ($this->dimensions as $dimension) {
            /** @var NodeDimension $dimension */
            $dimensionValues[$dimension->getName()][] = $dimension->getValue();
        }
        $this->dimensionsHash = self::sortDimensionValueArrayAndReturnDimensionsHash($dimensionValues);
        $this->dimensionValues = $dimensionValues;
    }

    /**
     * Sorts the incoming $dimensionValues array to make sure that before hashing, the ordering is made deterministic.
     * Then, calculates and returns the dimensionsHash.
     *
     * This method is public because it is used inside SiteImportService.
     *
     * @param array $dimensionValues, which will be ordered alphabetically
     * @return string the calculated DimensionsHash
     */
    public static function sortDimensionValueArrayAndReturnDimensionsHash(array &$dimensionValues)
    {
        foreach ($dimensionValues as &$values) {
            sort($values);
        }
        ksort($dimensionValues);

        return md5(json_encode($dimensionValues));
    }

    /**
     * Get a unique string for all dimension values
     *
     * Internal method
     *
     * @return string
     */
    public function getDimensionsHash()
    {
        return $this->dimensionsHash;
    }

    /**
     * Checks if this instance matches the given workspace and dimensions.
     *
     * @param Workspace $workspace
     * @param array $dimensions
     * @return boolean
     */
    public function matchesWorkspaceAndDimensions($workspace, array $dimensions = null)
    {
        if ($this->workspace->getName() !== $workspace->getName()) {
            return false;
        }
        if ($dimensions !== null) {
            $nodeDimensionValues = $this->dimensionValues;
            foreach ($dimensions as $dimensionName => $dimensionValues) {
                if (!isset($nodeDimensionValues[$dimensionName]) || array_intersect($nodeDimensionValues[$dimensionName], $dimensionValues) === array()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check if this NodeData object is a purely internal technical object (like a shadow node).
     * An internal NodeData should never produce a Node object.
     *
     * @return boolean
     */
    public function isInternal()
    {
        return $this->movedTo !== null && $this->removed === true;
    }

    /**
     * Move this NodeData to the given path and workspace.
     *
     * Basically 4 scenarios have to be covered here, depending on:
     *
     * - Does the NodeData have to be materialized (adapted to the workspace or target dimension)?
     * - Does a shadow node exist on the target path?
     *
     * Because unique key constraints and Doctrine ORM don't support arbitrary removal and update combinations,
     * existing NodeData instances are re-used and the metadata and content is swapped around.
     *
     * @param string $path
     * @param Workspace $workspace
     * @return NodeData If a shadow node was created this is the new NodeData object after the move.
     */
    public function move($path, $workspace)
    {
        $nodeData = $this;
        $originalPath = $this->path;
        if ($originalPath ===  $path) {
            return $this;
        }

        $targetPathShadowNodeData = $this->getExistingShadowNodeData($path, $workspace, $nodeData->getDimensionValues());

        if ($this->workspace->getName() !== $workspace->getName()) {
            if ($targetPathShadowNodeData === null) {
                // If there is no shadow node at the target path we need to materialize before moving and create a shadow node.
                $nodeData = $this->materializeToWorkspace($workspace);
                $nodeData->setPath($path, false);
                $movedNodeData = $nodeData;
            } else {
                // The existing shadow node on the target path will be used as the moved node. We don't need to materialize into the workspace.
                $movedNodeData = $targetPathShadowNodeData;
                $movedNodeData->setAsShadowOf(null);
                $movedNodeData->setIdentifier($nodeData->getIdentifier());
                $movedNodeData->similarize($nodeData);
                // A new shadow node will be created for the node data that references the recycled, existing shadow node
            }
            $movedNodeData->createShadow($originalPath);
        } else {
            $referencedShadowNode = $this->nodeDataRepository->findOneByMovedTo($nodeData);
            if ($targetPathShadowNodeData === null) {
                if ($referencedShadowNode === null) {
                    // There is no shadow node on the original or target path, so the current node data will be turned to a shadow node and a new node data will be created for the moved node.
                    // We cannot just create a new shadow node, since the order of Doctrine queries would cause unique key conflicts
                    $movedNodeData = new NodeData($path, $nodeData->getWorkspace(), $nodeData->getIdentifier(), $nodeData->getDimensionValues());
                    $movedNodeData->similarize($nodeData);
                    $this->addOrUpdate($movedNodeData);

                    $shadowNodeData = $nodeData;
                    $shadowNodeData->setAsShadowOf($movedNodeData);
                } else {
                    // A shadow node that references this node data already exists, so we just move the current node data
                    $movedNodeData = $nodeData;
                    $movedNodeData->setPath($path, false);
                }
            } else {
                if ($referencedShadowNode === null) {
                    // Turn the target path shadow node into the moved node (and adjust the identifier!)
                    // Do not reset the movedTo property to keep tracing the original move operation - TODO: Does that make sense if the identifier changes?
                    $movedNodeData = $targetPathShadowNodeData;
                    // Since the shadow node at the target path does not belong to the current node, we have to adjust the identifier
                    $movedNodeData->setRemoved(false);
                    $movedNodeData->setIdentifier($nodeData->getIdentifier());
                    $movedNodeData->similarize($nodeData);
                    // Create a shadow node from the current node that shadows the recycled node
                    $shadowNodeData = $nodeData;
                    $shadowNodeData->setAsShadowOf($movedNodeData);
                } else {
                    // If there is already shadow node on the target path, we need to make that shadow node the actual moved node and remove the current node data (which cannot be live).
                    // We cannot remove the shadow node and update the current node data, since the order of Doctrine queries would cause unique key conflicts.
                    $movedNodeData = $targetPathShadowNodeData;
                    $movedNodeData->setAsShadowOf(null);
                    $movedNodeData->similarize($nodeData);
                    $movedNodeData->setPath($path, false);
                    $this->nodeDataRepository->remove($nodeData);
                }
            }
        }

        return $movedNodeData;
    }

    /**
     * Find an existing shadow node data on the given path for the current node data of the node (used by setPath)
     *
     * @param string $path The (new) path of the node data
     * @param Workspace $workspace
     * @param array $dimensionValues
     * @return NodeData
     */
    protected function getExistingShadowNodeData($path, $workspace, $dimensionValues)
    {
        $existingShadowNode = $this->nodeDataRepository->findOneByPath($path, $workspace, $dimensionValues, true);
        if ($existingShadowNode !== null && $existingShadowNode->getMovedTo() !== null) {
            return $existingShadowNode;
        }
        return null;
    }

    /**
     * Create a shadow NodeData at the given path with the same workspace and dimensions as this
     *
     * Note: The constructor will already add the new object to the repository
     *
     * @param string $path The (original) path for the node data
     * @return NodeData
     */
    public function createShadow($path)
    {
        $shadowNode = new NodeData($path, $this->workspace, $this->identifier, $this->dimensionValues);
        $shadowNode->similarize($this);
        $shadowNode->setAsShadowOf($this);
        return $shadowNode;
    }

    /**
     * This becomes a shdow of the given NodeData object.
     * If NULL or no argument is given then movedTo is nulled and removed is set to FALSE effectively turning this into a normal NodeData.
     *
     * @param NodeData $nodeData
     * @return void
     */
    public function setAsShadowOf(NodeData $nodeData = null)
    {
        $this->setMovedTo($nodeData);
        $this->setRemoved(($nodeData !== null));
        $this->addOrUpdate();
    }

    /**
     * Materializes the original node data (of a different workspace) into the current
     * workspace, excluding content dimensions
     *
     * This is only used in setPath for now
     *
     * @param Workspace $workspace
     * @return NodeData
     */
    protected function materializeToWorkspace($workspace)
    {
        $newNodeData = new NodeData($this->path, $workspace, $this->identifier, $this->dimensionValues);
        $this->nodeDataRepository->add($newNodeData);

        $newNodeData->similarize($this);
        return $newNodeData;
    }

    /**
     * Adds this node to the Node Repository or updates it if it has been added earlier
     *
     * @param NodeData $nodeData Other NodeData object to addOrUpdate
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function addOrUpdate(NodeData $nodeData = null)
    {
        $nodeData = ($nodeData === null ? $this : $nodeData);

        // If this NodeData was previously removed and is in live workspace we don't want to add it again to persistence.
        if ($nodeData->isRemoved() && $this->workspace->getBaseWorkspace() === null) {
            // Just in case, normally setRemoved() should have already removed the object from the identityMap.
            if ($this->persistenceManager->isNewObject($nodeData) === false) {
                $nodeData->remove();
            }
        } else {
            if ($this->persistenceManager->isNewObject($nodeData)) {
                $this->nodeDataRepository->add($nodeData);
            } else {
                $this->nodeDataRepository->update($nodeData);
            }
        }
    }

    /**
     * Updates the attached content object
     *
     * @param object $contentObject
     * @return void
     */
    protected function updateContentObject($contentObject)
    {
        $this->persistenceManager->update($contentObject);
    }

    /**
     * Calculates the hash corresponding to the path of this instance.
     *
     * @return void
     */
    protected function calculatePathHash()
    {
        $this->pathHash = md5($this->path);
    }

    /**
     * Calculates the hash corresponding to the dimensions and their values for this instance.
     *
     * @return void
     */
    protected function calculateParentPathHash()
    {
        $this->parentPathHash = md5($this->parentPath);
    }

    /**
     * Create a fresh collection instance and clone dimensions
     *
     * @return void
     */
    public function __clone()
    {
        if ($this->dimensions instanceof Collection) {
            $existingDimensions = $this->dimensions->toArray();
            $this->dimensions = new ArrayCollection();
            /** @var NodeDimension $existingDimension */
            foreach ($existingDimensions as $existingDimension) {
                $this->dimensions->add(new NodeDimension($this, $existingDimension->getName(), $existingDimension->getValue()));
            }
        }
    }

    /**
     * Signals that a node has changed its path.
     *
     * @Flow\Signal
     * @param NodeData $nodeData the node data instance that has been changed
     * @return void
     */
    protected function emitNodePathChanged(NodeData $nodeData)
    {
    }
}
