<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Service\Cache\FirstLevelNodeCache;

/**
 * Context
 *
 * @api
 */
class Context
{
    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var Workspace
     */
    protected $workspace;

    /**
     * @var string
     */
    protected $workspaceName;

    /**
     * @var \DateTime
     */
    protected $currentDateTime;

    /**
     * If TRUE, invisible content elements will be shown.
     *
     * @var boolean
     */
    protected $invisibleContentShown = false;

    /**
     * If TRUE, removed content elements will be shown, even though they are removed.
     *
     * @var boolean
     */
    protected $removedContentShown = false;

    /**
     * If TRUE, even content elements will be shown which are not accessible by the currently logged in account.
     *
     * @var boolean
     */
    protected $inaccessibleContentShown = false;

    /**
     * @var array
     */
    protected $dimensions = array();

    /**
     * @var array
     */
    protected $targetDimensions = array();

    /**
     * @Flow\IgnoreValidation
     * @var FirstLevelNodeCache
     */
    protected $firstLevelNodeCache;

    /**
     * Creates a new Context object.
     *
     * NOTE: This is for internal use only, you should use the ContextFactory for creating Context instances.
     *
     * @param string $workspaceName
     * @param \DateTime $currentDateTime
     * @param array $dimensions Array of dimensions with array of ordered values
     * @param array $targetDimensions
     * @param boolean $invisibleContentShown
     * @param boolean $removedContentShown
     * @param boolean $inaccessibleContentShown
     * @return \TYPO3\TYPO3CR\Domain\Service\Context
     * @see ContextFactoryInterface
     */
    public function __construct($workspaceName, \DateTime $currentDateTime, array $dimensions, array $targetDimensions, $invisibleContentShown, $removedContentShown, $inaccessibleContentShown)
    {
        $this->workspaceName = $workspaceName;
        $this->currentDateTime = $currentDateTime;
        $this->dimensions = $dimensions;
        $this->invisibleContentShown = $invisibleContentShown;
        $this->removedContentShown = $removedContentShown;
        $this->inaccessibleContentShown = $inaccessibleContentShown;
        $this->targetDimensions = $targetDimensions;

        $this->firstLevelNodeCache = new FirstLevelNodeCache();
    }

    /**
     * Returns the current workspace.
     *
     * @param boolean $createWorkspaceIfNecessary If enabled, creates a workspace with the configured name if it doesn't exist already
     * @return Workspace The workspace or NULL
     * @api
     */
    public function getWorkspace($createWorkspaceIfNecessary = true)
    {
        if ($this->workspace === null) {
            $this->workspace = $this->workspaceRepository->findByIdentifier($this->workspaceName);
            if (!$this->workspace) {
                if ($createWorkspaceIfNecessary === false) {
                    return null;
                }
                $liveWorkspace = null;
                if ($this->workspaceName !== 'live') {
                    $liveWorkspace = $this->workspaceRepository->findByIdentifier('live');
                }
                if (!$liveWorkspace) {
                    $liveWorkspace = new Workspace('live');
                    $this->workspaceRepository->add($liveWorkspace);
                }
                if ($this->workspaceName === 'live') {
                    $this->workspace = $liveWorkspace;
                } else {
                    $this->workspace = new Workspace($this->workspaceName, $liveWorkspace);
                    $this->workspaceRepository->add($this->workspace);
                }
            }
        }
        return $this->workspace;
    }

    /**
     * Returns the name of the workspace.
     *
     * @return string
     * @api
     */
    public function getWorkspaceName()
    {
        return $this->workspaceName;
    }

    /**
     * Returns the current date and time in form of a \DateTime
     * object.
     *
     * If you use this method for getting the current date and time
     * everywhere in your code, it will be possible to simulate a certain
     * time in unit tests or in the actual application (for realizing previews etc).
     *
     * @return \DateTime The current date and time - or a simulated version of it
     * @api
     */
    public function getCurrentDateTime()
    {
        return $this->currentDateTime;
    }

    /**
     * Convenience method returns the root node for
     * this context workspace.
     *
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
     * @api
     */
    public function getRootNode()
    {
        return $this->getNode('/');
    }

    /**
     * Returns a node specified by the given absolute path.
     *
     * @param string $path Absolute path specifying the node
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
     * @throws \InvalidArgumentException
     * @api
     */
    public function getNode($path)
    {
        if (!is_string($path) || $path[0] !== '/') {
            throw new \InvalidArgumentException('Only absolute paths are allowed for Context::getNode()', 1284975105);
        }

        $path = strtolower($path);

        $workspaceRootNode = $this->getWorkspace()->getRootNodeData();
        $rootNode = $this->nodeFactory->createFromNodeData($workspaceRootNode, $this);
        if ($path !== '/') {
            $node = $this->firstLevelNodeCache->getByPath($path);
            if ($node === false) {
                $node = $rootNode->getNode(substr($path, 1));
                $this->firstLevelNodeCache->setByPath($path, $node);
            }
        } else {
            $node = $rootNode;
        }

        return $node;
    }

    /**
     * Get a node by identifier and this context
     *
     * @param string $identifier The identifier of a node
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The node with the given identifier or NULL if no such node exists
     */
    public function getNodeByIdentifier($identifier)
    {
        $node = $this->firstLevelNodeCache->getByIdentifier($identifier);
        if ($node !== false) {
            return $node;
        }
        $nodeData = $this->nodeDataRepository->findOneByIdentifier($identifier, $this->getWorkspace(false), $this->dimensions);
        if ($nodeData !== null) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $this);
        } else {
            $node = null;
        }
        $this->firstLevelNodeCache->setByIdentifier($identifier, $node);
        return $node;
    }

    /**
     * Get all node variants for the given identifier
     *
     * A variant of a node can have different dimension values and path (for non-aggregate nodes).
     * The resulting node instances might belong to a different context.
     *
     * @param string $identifier The identifier of a node
     * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
     */
    public function getNodeVariantsByIdentifier($identifier)
    {
        $nodeVariants = array();
        $nodeDataElements = $this->nodeDataRepository->findByIdentifierWithoutReduce($identifier, $this->getWorkspace(false));
        /** @var NodeData $nodeData */
        foreach ($nodeDataElements as $nodeData) {
            $contextProperties = $this->getProperties();
            $contextProperties['dimensions'] = $nodeData->getDimensionValues();
            unset($contextProperties['targetDimensions']);
            $adjustedContext = $this->contextFactory->create($contextProperties);
            $nodeVariant = $this->nodeFactory->createFromNodeData($nodeData, $adjustedContext);
            $nodeVariants[] = $nodeVariant;
        }
        return $nodeVariants;
    }

    /**
     * Finds all nodes lying on the path specified by (and including) the given
     * starting point and end point.
     *
     * @param mixed $startingPoint Either an absolute path or an actual node specifying the starting point, for example /sites/mysite.com/
     * @param mixed $endPoint Either an absolute path or an actual node specifying the end point, for example /sites/mysite.com/homepage/subpage
     * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> The nodes found between and including the given paths or an empty array of none were found
     * @api
     */
    public function getNodesOnPath($startingPoint, $endPoint)
    {
        $startingPointPath = ($startingPoint instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) ? $startingPoint->getPath() : $startingPoint;
        $endPointPath = ($endPoint instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) ? $endPoint->getPath() : $endPoint;

        $nodeDataElements = $this->nodeDataRepository->findOnPath($startingPointPath, $endPointPath, $this->getWorkspace(), $this->getDimensions(), $this->isRemovedContentShown());
        $nodes = array();
        foreach ($nodeDataElements as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $this);
            if ($node !== null) {
                $nodes[] = $node;
                $this->firstLevelNodeCache->setByPath($node->getPath(), $node);
            }
        }

        return $nodes;
    }

    /**
     * Adopts a node from a (possibly) different context to this context
     *
     * Checks if a node variant matching the exact dimensions already exists for this context and
     * return it if found. Otherwise a new node variant for this context is created.
     *
     * In case the node already exists in the context but does not match the target dimensions a
     * new, more specific node is created and returned.
     *
     * @param NodeInterface $node The node with a different context. If the context of the given node is the same as this context the operation will have no effect.
     * @param boolean $recursive If TRUE also adopt all descendant nodes which are non-aggregate
     * @return NodeInterface A new or existing node that matches this context
     */
    public function adoptNode(NodeInterface $node, $recursive = false)
    {
        if ($node->getContext() === $this && $node->dimensionsAreMatchingTargetDimensionValues()) {
            return $node;
        }

        $this->emitBeforeAdoptNode($node, $this, $recursive);

        $existingNode = $this->getNodeByIdentifier($node->getIdentifier());
        if ($existingNode !== null) {
            if ($existingNode->dimensionsAreMatchingTargetDimensionValues()) {
                $adoptedNode = $existingNode;
            } else {
                $adoptedNode = $existingNode->createVariantForContext($this);
            }
        } else {
            $adoptedNode = $node->createVariantForContext($this);
        }
        $this->firstLevelNodeCache->setByIdentifier($adoptedNode->getIdentifier(), $adoptedNode);

        if ($recursive) {
            $childNodes = $node->getChildNodes();
            /** @var NodeInterface $childNode */
            foreach ($childNodes as $childNode) {
                if (!$childNode->getNodeType()->isAggregate()) {
                    $this->adoptNode($childNode, true);
                }
            }
        }

        $this->emitAfterAdoptNode($node, $this, $recursive);

        return $adoptedNode;
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $node
     * @param Context $context
     * @param $recursive
     */
    protected function emitBeforeAdoptNode(NodeInterface $node, Context $context, $recursive)
    {
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $node
     * @param Context $context
     * @param $recursive
     */
    protected function emitAfterAdoptNode(NodeInterface $node, Context $context, $recursive)
    {
    }

    /**
     * Tells if nodes which are usually invisible should be accessible through the Node API and queries
     *
     * @return boolean
     * @see NodeFactory->filterNodeByContext()
     * @api
     */
    public function isInvisibleContentShown()
    {
        return $this->invisibleContentShown;
    }

    /**
     * Tells if nodes which have their "removed" flag set should be accessible through
     * the Node API and queries
     *
     * @return boolean
     * @see Node->filterNodeByContext()
     * @api
     */
    public function isRemovedContentShown()
    {
        return $this->removedContentShown;
    }

    /**
     * Tells if nodes which have access restrictions should be accessible through
     * the Node API and queries even without the necessary roles / rights
     *
     * @return boolean
     * @api
     */
    public function isInaccessibleContentShown()
    {
        return $this->inaccessibleContentShown;
    }

    /**
     * An indexed array of dimensions with ordered list of values for matching nodes by content dimensions
     *
     * @return array
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * An indexed array of dimensions with a set of values that should be applied when updating or creating
     *
     * @return array
     */
    public function getTargetDimensions()
    {
        return $this->targetDimensions;
    }

    /**
     * An indexed array of dimensions with a set of values that should be applied when updating or creating
     *
     * @return array
     */
    public function getTargetDimensionValues()
    {
        return array_map(function ($value) {
            return array($value);
        }, $this->getTargetDimensions());
    }

    /**
     * Returns the properties of this context.
     *
     * @return array
     */
    public function getProperties()
    {
        return array(
            'workspaceName' => $this->workspaceName,
            'currentDateTime' => $this->currentDateTime,
            'dimensions' => $this->dimensions,
            'targetDimensions' => $this->targetDimensions,
            'invisibleContentShown' => $this->invisibleContentShown,
            'removedContentShown' => $this->removedContentShown,
            'inaccessibleContentShown' => $this->inaccessibleContentShown
        );
    }

    /**
     * Not public API!
     *
     * @return FirstLevelNodeCache
     */
    public function getFirstLevelNodeCache()
    {
        return $this->firstLevelNodeCache;
    }
}
