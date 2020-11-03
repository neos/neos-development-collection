<?php
namespace Neos\ContentRepository\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\QueryBuilder;
use Neos\ContentRepository\Exception\NodeConfigurationException;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\Exception as PropertyException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Utility\Arrays;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Utility;

/**
 * Plugin for the ContentRepository NodeCommandController which provides functionality for creating missing child nodes.
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandControllerPlugin implements EventDispatchingNodeCommandControllerPluginInterface
{
    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var ConsoleOutput
     * @deprecated It's discouraged to interact with the console output directly. Instead use the event dispatching. @see dispatch()
     */
    protected $output;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @var array
     */
    protected $pluginConfigurations = [];

    /**
     * @var ContentDimensionCombinator
     * @Flow\Inject
     */
    protected $contentDimensionCombinator;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Callbacks to be invoked when an event is triggered
     *
     * @see dispatch()
     * @var \Closure[]
     */
    protected $eventCallbacks;

    /**
     * Returns a short description
     *
     * @param string $controllerCommandName Name of the command in question, for example "repair"
     * @return string A piece of text to be included in the overall description of the node:xy command
     */
    public static function getSubCommandShortDescription($controllerCommandName)
    {
        switch ($controllerCommandName) {
            case 'repair':
                return 'Run checks for basic node integrity in the content repository';
        }
        return '';
    }

    /**
     * Returns a piece of description for the specific task the plugin solves for the specified command
     *
     * @param string $controllerCommandName Name of the command in question, for example "repair"
     * @return string A piece of text to be included in the overall description of the node:xy command
     */
    public static function getSubCommandDescription($controllerCommandName)
    {
        switch ($controllerCommandName) {
            case 'repair':
                return <<<'HELPTEXT'
<u>Remove abstract and undefined node types</u>
removeAbstractAndUndefinedNodes

Will remove all nodes that has an abstract or undefined node type.

<u>Remove orphan (parentless) nodes</u>
removeOrphanNodes

Will remove all child nodes that do not have a connection to the root node.

<u>Remove disallowed child nodes</u>
removeDisallowedChildNodes

Will remove all child nodes that are disallowed according to the node type's auto-create
configuration and constraints.

<u>Remove undefined node properties</u>
removeUndefinedProperties

<u>Remove broken object references</u>
removeBrokenEntityReferences

Detects and removes references from nodes to entities which don't exist anymore (for
example Image nodes referencing ImageVariant objects which are gone for some reason).

Will remove all undefined properties according to the node type configuration.

<u>Remove nodes with invalid dimensions</u>
removeNodesWithInvalidDimensions

Will check for and optionally remove nodes which have dimension values not matching
the current content dimension configuration.

<u>Remove nodes with invalid workspace</u>
removeNodesWithInvalidWorkspace

Will check for and optionally remove nodes which belong to a workspace which no longer
exists..

<u>Repair inconsistent node identifiers</u>
fixNodesWithInconsistentIdentifier

Will check for and optionally repair node identifiers which are out of sync with their
corresponding nodes in a live workspace.

<u>Missing child nodes</u>
createMissingChildNodes

For all nodes (or only those which match the --node-type filter specified with this
command) which currently don't have child nodes as configured by the node type's
configuration new child nodes will be created.

<u>Reorder child nodes</u>
reorderChildNodes

For all nodes (or only those which match the --node-type filter specified with this
command) which have configured child nodes, those child nodes are reordered according to the
position from the parents NodeType configuration.

<u>Missing default properties</u>
addMissingDefaultValues

For all nodes (or only those which match the --node-type filter specified with this
command) which currently don\t have a property that have a default value configuration
the default value for that property will be set.

<u>Repair nodes with missing shadow nodes</u>
repairShadowNodes

This will reconstruct missing shadow nodes in case something went wrong in creating
or publishing them. This must be used on a workspace other than live.

It searches for nodes which have a corresponding node in one of the base workspaces,
have different node paths, but don't have a corresponding shadow node with a "movedto"
value.

HELPTEXT;
        }
        return '';
    }

    /**
     * A method which runs the task implemented by the plugin for the given command
     *
     * @param string $controllerCommandName Name of the command in question, for example "repair"
     * @param ConsoleOutput $output (unused)
     * @param NodeType $nodeType Only handle this node type (if specified)
     * @param string $workspaceName Only handle this workspace (if specified)
     * @param boolean $dryRun If true, don't do any changes, just simulate what you would do
     * @param boolean $cleanup If false, cleanup tasks are skipped
     * @param string $skip Skip the given check or checks (comma separated)
     * @param string $only Only execute the given check or checks (comma separated)
     * @return void
     */
    public function invokeSubCommand($controllerCommandName, ConsoleOutput $output, NodeType $nodeType = null, $workspaceName = 'live', $dryRun = false, $cleanup = true, $skip = null, $only = null)
    {
        /** @noinspection PhpDeprecationInspection This is only set for backwards compatibility */
        $this->output = $output;
        $commandMethods = [
            'removeAbstractAndUndefinedNodes' => [ 'cleanup' => true ],
            'removeOrphanNodes' => [ 'cleanup' => true ],
            'removeDisallowedChildNodes' => [ 'cleanup' => true ],
            'removeUndefinedProperties' => [ 'cleanup' => true ],
            'removeBrokenEntityReferences' => [ 'cleanup' => true ],
            'removeNodesWithInvalidDimensions' => [ 'cleanup' => true ],
            'removeNodesWithInvalidWorkspace' => [ 'cleanup' => true ],
            'fixNodesWithInconsistentIdentifier' => [ 'cleanup' => false ],
            'createMissingChildNodes' => [ 'cleanup' => false ],
            'reorderChildNodes' => [ 'cleanup' => false ],
            'addMissingDefaultValues' => [ 'cleanup' => false ],
            'repairShadowNodes' => [ 'cleanup' => false ]
        ];
        $skipCommandNames = Arrays::trimExplode(',', ($skip === null ? '' : $skip));
        $onlyCommandNames = Arrays::trimExplode(',', ($only === null ? '' : $only));

        switch ($controllerCommandName) {
            case 'repair':
                foreach ($commandMethods as $commandMethodName => $commandMethodConfiguration) {
                    if (in_array($commandMethodName, $skipCommandNames)) {
                        continue;
                    }
                    if ($onlyCommandNames !== [] && !in_array($commandMethodName, $onlyCommandNames)) {
                        continue;
                    }
                    if (!$cleanup && $commandMethodConfiguration['cleanup']) {
                        continue;
                    }
                    $this->$commandMethodName($workspaceName, $dryRun, $nodeType);
                }
        }
    }

    /**
     * Performs checks for missing child nodes according to the node's auto-create configuration and creates
     * them if necessary.
     *
     * @param string $workspaceName Name of the workspace to consider
     * @param boolean $dryRun Simulate?
     * @param NodeType $nodeType Only for this node type, if specified
     * @return void
     * @throws NodeConfigurationException
     * @throws NodeTypeNotFoundException
     */
    protected function createMissingChildNodes($workspaceName, $dryRun, NodeType $nodeType = null)
    {
        if ($nodeType !== null) {
            $this->dispatch(self::EVENT_NOTICE, sprintf('Checking nodes of type "<i>%s</i>" for missing child nodes ...', $nodeType->getName()));
            $this->createChildNodesByNodeType($nodeType, $workspaceName, $dryRun);
        } else {
            $this->dispatch(self::EVENT_NOTICE, 'Checking for missing child nodes ...');
            foreach ($this->nodeTypeManager->getNodeTypes() as $nodeType) {
                /** @var NodeType $nodeType */
                if ($nodeType->isAbstract()) {
                    continue;
                }
                $this->createChildNodesByNodeType($nodeType, $workspaceName, $dryRun);
            }
        }

        $this->persistenceManager->persistAll();
    }

    /**
     * Create missing child nodes for the given node type
     *
     * @param NodeType $nodeType
     * @param string $workspaceName
     * @param boolean $dryRun
     * @return void
     * @throws NodeTypeNotFoundException
     * @throws NodeConfigurationException
     */
    protected function createChildNodesByNodeType(NodeType $nodeType, $workspaceName, $dryRun)
    {
        $createdNodesCount = 0;
        $updatedNodesCount = 0;
        $incorrectNodeTypeCount = 0;
        $nodeCreationExceptions = 0;

        $nodeIdentifiersWhichNeedUpdate = [];

        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), false);
        $nodeTypes[$nodeType->getName()] = $nodeType;

        if ($this->nodeTypeManager->hasNodeType((string)$nodeType)) {
            $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeType);
            $nodeTypeNames[$nodeType->getName()] = $nodeType;
        } else {
            $this->dispatch(self::EVENT_ERROR, sprintf('Node type "<i>%s</i>" does not exist', (string)$nodeType));
            return;
        }

        /** @var $nodeType NodeType */
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            $childNodes = $nodeType->getAutoCreatedChildNodes();
            if (count($childNodes) === 0) {
                continue;
            }
            foreach ($this->getNodeDataByNodeTypeAndWorkspace($nodeTypeName, $workspaceName) as $nodeData) {
                $context = $this->nodeFactory->createContextMatchingNodeData($nodeData);
                $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
                if (!$node instanceof NodeInterface) {
                    continue;
                }
                foreach ($childNodes as $childNodeName => $childNodeType) {
                    $childNode = $node->getNode($childNodeName);
                    $childNodeIdentifier = Utility::buildAutoCreatedChildNodeIdentifier($childNodeName, $node->getIdentifier());
                    if ($childNode === null) {
                        $taskDescription = sprintf('Add node <i>%s</i> named "<i>%s</i>" in "<i>%s</i>"', $childNodeIdentifier, $childNodeName, $node->getPath());
                        $taskClosure = function () use ($node, $childNodeName, $childNodeType, $childNodeIdentifier, &$nodeCreationExceptions) {
                            try {
                                $node->createNode($childNodeName, $childNodeType, $childNodeIdentifier);
                            } catch (\Exception $exception) {
                                $this->dispatch(self::EVENT_WARNING, sprintf('Could not create node named "<i>%s</i>" in "<i>%s</i>" (<i>%s</i>)', $childNodeName, $node->getPath(), $exception->getMessage()));
                                $nodeCreationExceptions++;
                            }
                        };
                        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
                        $createdNodesCount++;
                    } elseif ($childNode->isRemoved() === true) {
                        $taskDescription = sprintf('Undelete node <i>%s</i> named "<i>%s</i>" in "<i>%s</i>"', $childNodeIdentifier, $childNodeName, $node->getPath());
                        $taskClosure = function () use ($node) {
                            $node->setRemoved(false);
                        };
                        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
                        $createdNodesCount++;
                    } elseif ($childNode->getIdentifier() !== $childNodeIdentifier) {
                        $nodeIdentifiersWhichNeedUpdate[$childNode->getIdentifier()] = $childNodeIdentifier;
                    } elseif ($childNode->getNodeType() !== $childNodeType) {
                        $taskDescription = sprintf('Set node type of node <i>%s</i>: <i>%s</i> => <i>%s</i>', $childNodeIdentifier, $childNode->getNodeType(), $childNodeType);
                        $taskClosure = function () use ($childNode, $childNodeType) {
                            $childNode->setNodeType($childNodeType);
                        };
                        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
                        $incorrectNodeTypeCount++;
                    }
                }
            }
        }
        foreach ($nodeIdentifiersWhichNeedUpdate as $oldNodeIdentifier => $newNodeIdentifier) {
            $taskDescription = sprintf('Update node identifier from <i>%s</i> to <i>%s</i> because it is not a "stable" identifier', $oldNodeIdentifier, $newNodeIdentifier);
            $taskClosure = function () use ($oldNodeIdentifier, $newNodeIdentifier) {
                $queryBuilder = $this->entityManager->createQueryBuilder();
                $queryBuilder->update(NodeData::class, 'n')
                    ->set('n.identifier', $queryBuilder->expr()->literal($newNodeIdentifier))
                    ->where('n.identifier = :oldNodeIdentifier')
                    ->setParameter('oldNodeIdentifier', $oldNodeIdentifier);
                $queryBuilder->getQuery()->getResult();
            };
            $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
            $updatedNodesCount++;
        }

        if ($createdNodesCount !== 0 || $nodeCreationExceptions !== 0 || $updatedNodesCount !== 0 || $incorrectNodeTypeCount !== 0) {
            if ($dryRun === false) {
                if ($createdNodesCount > 0) {
                    $this->dispatch(self::EVENT_NOTICE, sprintf('Created <i>%d</i> new child nodes', $createdNodesCount));
                }
                if ($updatedNodesCount > 0) {
                    $this->dispatch(self::EVENT_NOTICE, sprintf('Updated identifier of <i>%d</i> child nodes', $updatedNodesCount));
                }
                if ($incorrectNodeTypeCount > 0) {
                    $this->dispatch(self::EVENT_NOTICE, sprintf('Changed node type of <i>%d</i> child nodes', $incorrectNodeTypeCount));
                }
                if ($nodeCreationExceptions > 0) {
                    $this->dispatch(self::EVENT_NOTICE, sprintf('<i>%d</i> Errors occurred during child node creation', $nodeCreationExceptions));
                }
                $this->persistenceManager->persistAll();
            } else {
                if ($createdNodesCount > 0) {
                    $this->dispatch(self::EVENT_NOTICE, sprintf('<i>%d</i> missing child nodes need to be created', $createdNodesCount));
                }
                if ($updatedNodesCount > 0) {
                    $this->dispatch(self::EVENT_NOTICE, sprintf('<i>%d</i> identifiers of child nodes need to be updated', $updatedNodesCount));
                }
                if ($incorrectNodeTypeCount > 0) {
                    $this->dispatch(self::EVENT_NOTICE, sprintf('<i>%d</i> child nodes have incorrect node type', $incorrectNodeTypeCount));
                }
            }
        }
    }

    /**
     * Performs checks for unset properties that has default values and sets them if necessary.
     *
     * @param string $workspaceName Name of the workspace to consider
     * @param boolean $dryRun Simulate?
     * @param NodeType $nodeType Only for this node type, if specified
     * @return void
     * @throws NodeConfigurationException
     * @throws NodeTypeNotFoundException
     */
    public function addMissingDefaultValues($workspaceName, $dryRun, NodeType $nodeType = null)
    {
        if ($nodeType !== null) {
            $this->dispatch(self::EVENT_NOTICE, sprintf('Checking nodes of type <i>%s</i> for missing default values ...', $nodeType));
            $this->addMissingDefaultValuesByNodeType($nodeType, $workspaceName, $dryRun);
        } else {
            $this->dispatch(self::EVENT_NOTICE, 'Checking for missing default values ...');
            foreach ($this->nodeTypeManager->getNodeTypes() as $nodeType) {
                /** @var NodeType $nodeType */
                if ($nodeType->isAbstract()) {
                    continue;
                }
                $this->addMissingDefaultValuesByNodeType($nodeType, $workspaceName, $dryRun);
            }
        }
    }

    /**
     * Adds missing default values for the given node type
     *
     * @param NodeType $nodeType
     * @param string $workspaceName
     * @param boolean $dryRun
     * @return void
     * @throws NodeConfigurationException
     * @throws NodeTypeNotFoundException
     */
    public function addMissingDefaultValuesByNodeType(NodeType $nodeType, $workspaceName, $dryRun)
    {
        $addedMissingDefaultValuesCount = 0;

        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), false);
        $nodeTypes[$nodeType->getName()] = $nodeType;

        if ($this->nodeTypeManager->hasNodeType((string)$nodeType)) {
            $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeType);
            $nodeTypeNames[$nodeType->getName()] = $nodeType;
        } else {
            $this->dispatch(self::EVENT_ERROR, sprintf('Node type <i>%s</i> does not exist', $nodeType));
            return;
        }

        /** @var $nodeType NodeType */
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            $defaultValues = $nodeType->getDefaultValuesForProperties();
            foreach ($this->getNodeDataByNodeTypeAndWorkspace($nodeTypeName, $workspaceName) as $nodeData) {
                /** @var NodeData $nodeData */
                $context = $this->nodeFactory->createContextMatchingNodeData($nodeData);
                $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
                if (!$node instanceof NodeInterface) {
                    continue;
                }
                if ($node instanceof Node && !$node->dimensionsAreMatchingTargetDimensionValues()) {
                    if ($node->getNodeData()->getDimensionValues() === []) {
                        $this->dispatch(self::EVENT_NOTICE, sprintf('Skipping node <i>%s</i> because it has no dimension values set', $node->getPath()));
                    } else {
                        $this->dispatch(self::EVENT_NOTICE, sprintf('Skipping node <i>%s</i> because it has invalid dimension values: %s', $node->getPath(), json_encode($node->getNodeData()->getDimensionValues())));
                    }
                    continue;
                }

                foreach ($defaultValues as $propertyName => $defaultValue) {
                    if ($propertyName[0] === '_' || $node->hasProperty($propertyName)) {
                        continue;
                    }

                    $taskDescription = sprintf('Set default value for property named "<i>%s</i>" in "<i>%s</i>" (<i>%s</i>)', $propertyName, $node->getPath(), $node->getNodeType()->getName());
                    $taskClosure = function () use ($node, $propertyName, $defaultValue) {
                        $node->setProperty($propertyName, $defaultValue);
                    };
                    $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
                    $addedMissingDefaultValuesCount++;
                }
            }
        }

        if ($addedMissingDefaultValuesCount !== 0) {
            if ($dryRun === false) {
                $this->persistenceManager->persistAll();
                $this->dispatch(self::EVENT_NOTICE, sprintf('Added <i>%d</i> new default values', $addedMissingDefaultValuesCount));
            } else {
                $this->dispatch(self::EVENT_NOTICE, sprintf('<i>%d</i> missing default values need to be set', $addedMissingDefaultValuesCount));
            }
        }
    }

    /**
     * Performs checks for nodes with abstract or undefined node types and removes them if found.
     *
     * @param string $workspaceName
     * @return void
     */
    protected function removeAbstractAndUndefinedNodes($workspaceName)
    {
        $this->dispatch(self::EVENT_NOTICE, 'Checking for nodes with abstract or undefined node types ...');

        $abstractNodeTypes = [];
        $nonAbstractNodeTypes = [];
        foreach ($this->nodeTypeManager->getNodeTypes() as $nodeType) {
            /** @var NodeType $nodeType */
            if ($nodeType->isAbstract()) {
                $abstractNodeTypes[] = $nodeType->getName();
            } else {
                $nonAbstractNodeTypes[] = $nodeType->getName();
            }
        }

        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder
            ->select('n')
            ->distinct()
            ->from(NodeData::class, 'n')
            ->where('n.nodeType NOT IN (:excludeNodeTypes)')
            ->setParameter('excludeNodeTypes', $nonAbstractNodeTypes)
            ->andWhere('n.workspace = :workspace')
            ->setParameter('workspace', $workspaceName);

        $nodes = $queryBuilder->getQuery()->getArrayResult();

        $removableNodesCount = count($nodes);
        if ($removableNodesCount === 0) {
            return;
        }

        foreach ($nodes as $node) {
            $name = $node['path'] === '/' ? '' : substr($node['path'], strrpos($node['path'], '/') + 1);
            $type = in_array($node['nodeType'], $abstractNodeTypes) ? 'abstract' : 'undefined';
            $this->dispatch(self::EVENT_NOTICE, sprintf('Found node with %s node type named "<i>%s</i>" (<i>%s</i>) in "<i>%s</i>"', $type, $name, $node['nodeType'], $node['path']));
        }
        $taskDescription = sprintf('Remove <i>%d</i> node%s with abstract or undefined node types', $removableNodesCount, $removableNodesCount > 1 ? 's' : '');
        $taskClosure = function () use ($nodes) {
            foreach ($nodes as $node) {
                $this->removeNode($node['identifier'], $node['dimensionsHash']);
            }
        };
        $taskRequiresConfirmation = true;
        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure, $taskRequiresConfirmation);
    }

    /**
     * Performs checks for disallowed child nodes according to the node's auto-create configuration and constraints
     * and removes them if found.
     *
     * @param string $workspaceName
     * @return void
     */
    protected function removeDisallowedChildNodes($workspaceName)
    {
        $this->dispatch(self::EVENT_NOTICE, 'Checking for disallowed child nodes ...');

        /** @var \Neos\ContentRepository\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);

        $nodes = [];
        $nodeExceptionCount = 0;
        $removeDisallowedChildNodes = function (NodeInterface $node) use (&$removeDisallowedChildNodes, &$nodes, &$nodeExceptionCount) {
            try {
                foreach ($node->getChildNodes() as $childNode) {
                    /** @var $childNode NodeInterface */
                    if (!$childNode->isAutoCreated() && !$node->isNodeTypeAllowedAsChildNode($childNode->getNodeType())) {
                        $nodes[] = $childNode;
                        $parent = $node->isAutoCreated() ? $node->getParent() : $node;
                        $this->dispatch(self::EVENT_NOTICE, sprintf('Found disallowed node named "<i>%s</i>" (<i>%s</i>) in "<i>%s</i>", child of node "<i>%s</i>" (<i>%s</i>)', $childNode->getName(), $childNode->getNodeType()->getName(), $childNode->getPath(), $parent->getName(), $parent->getNodeType()->getName()));
                    } else {
                        $removeDisallowedChildNodes($childNode);
                    }
                }
            } catch (\Exception $exception) {
                $this->dispatch(self::EVENT_WARNING, sprintf('Error while traversing child nodes of node <i>%s</i>: %s (%s)', $node->getIdentifier(), $exception->getMessage(), $exception->getCode()));
                $nodeExceptionCount++;
            }
        };

        // TODO: Performance could be improved by a search for all child node data instead of looping over all contexts
        foreach ($this->contentDimensionCombinator->getAllAllowedCombinations() as $dimensionConfiguration) {
            $context = $this->createContext($workspace->getName(), $dimensionConfiguration);
            $removeDisallowedChildNodes($context->getRootNode());
            $context->getFirstLevelNodeCache()->flush();
            $this->nodeFactory->reset();
        }

        $disallowedChildNodesCount = count($nodes);
        if ($disallowedChildNodesCount > 0) {
            $taskDescription = sprintf('Remove <i>%d</i> disallowed node%s.', $disallowedChildNodesCount, $disallowedChildNodesCount > 1 ? 's' : '');
            $taskClosure = function () use ($nodes, $workspaceName) {
                foreach ($nodes as $node) {
                    $this->removeNodeAndChildNodesInWorkspaceByPath($node->getPath(), $workspaceName);
                }
            };
            $taskRequiresConfirmation = true;
            $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure, $taskRequiresConfirmation);

            if ($nodeExceptionCount > 0) {
                $this->dispatch(self::EVENT_NOTICE, '<i>%d</i> error%s occurred during child node traversing.', $nodeExceptionCount, $nodeExceptionCount > 1 ? 's' : '');
            }
        }
    }

    /**
     * Performs checks for orphan nodes removes them if found.
     *
     * @param string $workspaceName
     * @param boolean $dryRun (unused)
     * @param NodeType $nodeType Only for this node type, if specified
     * @return void
     */
    protected function removeOrphanNodes($workspaceName, /** @noinspection PhpUnusedParameterInspection */$dryRun, NodeType $nodeType = null)
    {
        $this->dispatch(self::EVENT_NOTICE, 'Checking for orphan nodes ...');

        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $workspaceList = [];
        /** @var \Neos\ContentRepository\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        while ($workspace !== null) {
            $workspaceList[] = $workspace->getName();
            $workspace = $workspace->getBaseWorkspace();
        }

        $query = $queryBuilder
            ->select('n')
            ->from(NodeData::class, 'n')
            ->leftJoin(
                NodeData::class,
                'n2',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'n.parentPathHash = n2.pathHash AND n2.workspace IN (:workspaceList)'
            )
            ->where('n2.path IS NULL')
            ->andWhere($queryBuilder->expr()->not('n.path = :slash'))
            ->andWhere('n.workspace = :workspace');
        $parameters = ['workspaceList' => $workspaceList, 'slash' => '/', 'workspace' => $workspaceName];

        if ($nodeType !== null) {
            $query->andWhere('n.nodeType = :nodetype');
            $parameters['nodetype'] = $nodeType;
        }

        $nodes = $query
            ->setParameters($parameters)
            ->getQuery()->getArrayResult();

        $nodesToBeRemoved = count($nodes);
        if ($nodesToBeRemoved === 0) {
            return;
        }

        foreach ($nodes as $node) {
            $name = $node['path'] === '/' ? '' : substr($node['path'], strrpos($node['path'], '/') + 1);
            $this->dispatch(self::EVENT_NOTICE, sprintf('Found orphan node named "<i>%s</i>" (<i>%s</i>) in "<i>%s</i>"', $name, $node['nodeType'], $node['path']));
        }

        $taskDescription = sprintf('Remove <i>%d</i> orphan node%s', $nodesToBeRemoved, $nodesToBeRemoved > 1 ? 's' : '');
        $taskClosure = function () use ($nodes, $workspaceName) {
            foreach ($nodes as $node) {
                $this->removeNodeAndChildNodesInWorkspaceByPath($node['path'], $workspaceName);
            }
        };
        $taskRequiresConfirmation = true;
        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure, $taskRequiresConfirmation);
    }

    /**
     * Performs checks for orphan nodes removes them if found.
     *
     * @param string $workspaceName
     * @param boolean $dryRun (unused)
     * @param NodeType $nodeType Only for this node type, if specified
     * @return void
     * @throws NodeConfigurationException
     */
    public function removeUndefinedProperties($workspaceName, /** @noinspection PhpUnusedParameterInspection */$dryRun, NodeType $nodeType = null)
    {
        $this->dispatch(self::EVENT_NOTICE, 'Checking for undefined properties ...');

        /** @var \Neos\ContentRepository\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);

        $nodesWithUndefinedPropertiesNodes = [];
        $undefinedPropertiesCount = 0;
        $nodes = $nodeType !== null ? $this->getNodeDataByNodeTypeAndWorkspace($nodeType, $workspaceName) : $this->nodeDataRepository->findByWorkspace($workspace);
        foreach ($nodes as $nodeData) {
            try {
                /** @var NodeData $nodeData */
                if ($nodeData->getNodeType()->getName() === 'unstructured') {
                    continue;
                }
                $context = $this->nodeFactory->createContextMatchingNodeData($nodeData);
                $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
                if (!$node instanceof NodeInterface) {
                    continue;
                }
                $nodeType = $node->getNodeType();

                $nodeTypePropertyNames = array_keys($nodeType->getProperties());
                $undefinedProperties = [];

                foreach ($node->getProperties() as $propertyName => $propertyValue) {
                    if (!in_array($propertyName, $nodeTypePropertyNames)) {
                        $undefinedProperties[] = $propertyName;
                    }
                }
                if ($undefinedProperties !== []) {
                    $nodesWithUndefinedPropertiesNodes[$node->getIdentifier()] = ['node' => $node, 'undefinedProperties' => $undefinedProperties];
                    foreach ($undefinedProperties as $undefinedProperty) {
                        $undefinedPropertiesCount++;
                        $this->dispatch(self::EVENT_NOTICE, sprintf('Found undefined property named "<i>%s</i>" in "<i>%s</i>" (<i>%s</i>)', $undefinedProperty, $node->getPath(), $node->getNodeType()->getName()));
                    }
                }
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (NodeTypeNotFoundException $exception) {
                $this->dispatch(self::EVENT_NOTICE, sprintf('Skipped undefined node type in "%s"', $nodeData->getPath()));
            }
        }

        if ($undefinedPropertiesCount > 0) {
            $taskDescription = sprintf('Remove <i>%d</i> undefined propert%s.', $undefinedPropertiesCount, $undefinedPropertiesCount > 1 ? 'ies' : 'y');
            $taskClosure = function () use ($nodesWithUndefinedPropertiesNodes) {
                foreach ($nodesWithUndefinedPropertiesNodes as $nodesWithUndefinedPropertiesNode) {
                    /** @var NodeInterface $node */
                    $node = $nodesWithUndefinedPropertiesNode['node'];
                    foreach ($nodesWithUndefinedPropertiesNode['undefinedProperties'] as $undefinedProperty) {
                        if ($node->hasProperty($undefinedProperty)) {
                            $node->removeProperty($undefinedProperty);
                        }
                    }
                }
            };
            $taskRequiresConfirmation = true;
            $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure, $taskRequiresConfirmation);
        }

        $this->persistenceManager->persistAll();
    }

    /**
     * Remove broken entity references
     *
     * This removes references from nodes to entities which don't exist anymore.
     *
     * @param string $workspaceName
     * @return void
     * @throws NodeException
     * @throws PropertyException
     * @throws SecurityException
     */
    public function removeBrokenEntityReferences($workspaceName)
    {
        $this->dispatch(self::EVENT_NOTICE, 'Checking for broken entity references ...');

        /** @var \Neos\ContentRepository\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);

        $nodeTypesWithEntityReferences = [];
        foreach ($this->nodeTypeManager->getNodeTypes() as $nodeType) {
            /** @var NodeType $nodeType */
            foreach (array_keys($nodeType->getProperties()) as $propertyName) {
                $propertyType = $nodeType->getPropertyType($propertyName);
                if (strpos($propertyType, '\\') !== false) {
                    if (!isset($nodeTypesWithEntityReferences[$nodeType->getName()])) {
                        $nodeTypesWithEntityReferences[$nodeType->getName()] = [];
                    }
                    $nodeTypesWithEntityReferences[$nodeType->getName()][$propertyName] = $propertyType;
                }
            }
        }

        $nodesWithBrokenEntityReferences = [];
        $brokenReferencesCount = 0;
        foreach ($nodeTypesWithEntityReferences as $nodeTypeName => $properties) {
            $nodeDatas = $this->nodeDataRepository->findByParentAndNodeTypeRecursively('/', $nodeTypeName, $workspace);
            foreach ($nodeDatas as $nodeData) {
                /** @var NodeData $nodeData */
                foreach ($properties as $propertyName => $propertyType) {
                    $propertyValue = $nodeData->getProperty($propertyName);
                    $convertedProperty = null;

                    if (is_object($propertyValue)) {
                        $convertedProperty = $propertyValue;
                    }
                    if (is_string($propertyValue) && strlen($propertyValue) === 36) {
                        $convertedProperty = $this->propertyMapper->convert($propertyValue, $propertyType);
                        if ($convertedProperty === null) {
                            $nodesWithBrokenEntityReferences[$nodeData->getIdentifier()][$propertyName] = $nodeData;
                            $this->dispatch(self::EVENT_NOTICE, sprintf('Broken reference in "<i>%s</i>", property "<i>%s</i>" (<i>%s</i>) referring to <i>%s</i>.', $nodeData->getPath(), $nodeData->getIdentifier(), $propertyName, $propertyType, $propertyValue));
                            $brokenReferencesCount ++;
                        }
                    }
                    if ($convertedProperty instanceof Proxy) {
                        try {
                            $convertedProperty->__load();
                        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (EntityNotFoundException $e) {
                            $nodesWithBrokenEntityReferences[$nodeData->getIdentifier()][$propertyName] = $nodeData;
                            $this->dispatch(self::EVENT_NOTICE, sprintf('Broken reference in "<i>%s</i>", property "<i>%s</i>" (<i>%s</i>) referring to <i>%s</i>.', $nodeData->getPath(), $nodeData->getIdentifier(), $propertyName, $propertyType, $propertyValue));
                            $brokenReferencesCount ++;
                        }
                    }
                }
            }
        }

        if ($brokenReferencesCount === 0) {
            return;
        }
        $taskDescription = sprintf('Remove <i>%d</i> broken entity reference%s.', $brokenReferencesCount, $brokenReferencesCount > 1 ? 's' : '');
        $taskClosure = function () use ($nodesWithBrokenEntityReferences) {
            foreach ($nodesWithBrokenEntityReferences as $nodeIdentifier => $properties) {
                foreach ($properties as $propertyName => $nodeData) {
                    /** @var NodeData $nodeData */
                    $nodeData->setProperty($propertyName, null);
                }
            }
            $this->persistenceManager->persistAll();
        };
        $taskRequiresConfirmation = true;
        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure, $taskRequiresConfirmation);
    }

    /**
     * Creates a content context for given workspace
     *
     * @param string $workspaceName
     * @param array $dimensions
     * @return Context
     */
    protected function createContext($workspaceName, $dimensions)
    {
        return $this->contextFactory->create([
            'workspaceName' => $workspaceName,
            'dimensions' => $dimensions,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true,
            'removedContentShown' => true
        ]);
    }

    /**
     * Retrieves all NodeData objects of a certain node type inside a given workspace.
     *
     * Shadow nodes are excluded, because they will be published when publishing the moved node.
     *
     * @param string $nodeType
     * @param string $workspaceName
     * @return array<NodeData>
     */
    protected function getNodeDataByNodeTypeAndWorkspace($nodeType, $workspaceName)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('n')
            ->distinct()
            ->from(NodeData::class, 'n')
            ->where('n.nodeType = :nodeType')
            ->andWhere('n.workspace = :workspace')
            ->andWhere('n.movedTo IS NULL OR n.removed = :removed')
            ->setParameter('nodeType', $nodeType)
            ->setParameter('workspace', $workspaceName)
            ->setParameter('removed', false, \PDO::PARAM_BOOL);
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Removes all nodes with a specific path and their children in the given workspace.
     *
     * @param string $nodePath
     * @param string $workspaceName
     */
    protected function removeNodeAndChildNodesInWorkspaceByPath($nodePath, $workspaceName)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder
            ->resetDQLParts()
            ->delete(NodeData::class, 'n')
            ->where('n.path LIKE :path')
            ->orWhere('n.path LIKE :subpath')
            ->andWhere('n.workspace = :workspace')
            ->setParameters(['path' => $nodePath, 'subpath' => $nodePath . '/%', 'workspace' => $workspaceName])
            ->getQuery()
            ->execute();
    }

    /**
     * Removes the specified node (exactly that one)
     *
     * @param string $nodeIdentifier
     * @param string $dimensionsHash
     */
    protected function removeNode($nodeIdentifier, $dimensionsHash)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder
            ->resetDQLParts()
            ->delete(NodeData::class, 'n')
            ->where('n.identifier = :identifier')
            ->andWhere('n.dimensionsHash = :dimensionsHash')
            ->setParameters(['identifier' => $nodeIdentifier, 'dimensionsHash' => $dimensionsHash])
            ->getQuery()
            ->execute();
    }

    /**
     * Remove nodes with invalid dimension values
     *
     * This removes nodes which have dimension values not fitting to the current dimension configuration
     *
     * @param string $workspaceName Name of the workspace to consider
     * @return void
     */
    public function removeNodesWithInvalidDimensions($workspaceName)
    {
        $this->dispatch(self::EVENT_NOTICE, 'Checking for nodes with invalid dimensions ...');

        $allowedDimensionCombinations = $this->contentDimensionCombinator->getAllAllowedCombinations();
        $nodesArray = $this->collectNodesWithInvalidDimensions($workspaceName, $allowedDimensionCombinations);
        if ($nodesArray === []) {
            return;
        }
        $numberOfNodes = count($nodesArray);
        $taskDescription = sprintf('Remove <i>%d</i> node%s with invalid dimension values', $numberOfNodes, $numberOfNodes > 1 ? 's' : '');
        $taskClosure = function () use ($nodesArray) {
            foreach ($nodesArray as $nodeArray) {
                $this->removeNode($nodeArray['identifier'], $nodeArray['dimensionsHash']);
            }
        };
        $taskRequiresConfirmation = true;
        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure, $taskRequiresConfirmation);
    }

    /**
     * Collects all nodes of the given node type which have dimension values not fitting to the current dimension
     * configuration.
     *
     * @param string $workspaceName
     * @param array $allowedDimensionCombinations
     * @return array
     */
    protected function collectNodesWithInvalidDimensions($workspaceName, array $allowedDimensionCombinations)
    {
        $nodes = [];
        ksort($allowedDimensionCombinations);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('n')
            ->from(NodeData::class, 'n')
            ->where('n.workspace = :workspace')
            ->setParameter('workspace', $workspaceName);

        foreach ($queryBuilder->getQuery()->getArrayResult() as $nodeDataArray) {
            if ($nodeDataArray['dimensionValues'] === [] || $nodeDataArray['dimensionValues'] === '') {
                continue;
            }
            $foundValidDimensionValues = false;
            foreach ($allowedDimensionCombinations as $allowedDimensionConfiguration) {
                ksort($allowedDimensionConfiguration);
                ksort($nodeDataArray['dimensionValues']);
                foreach ($allowedDimensionConfiguration as $allowedDimensionKey => $allowedDimensionValuesArray) {
                    if (isset($nodeDataArray['dimensionValues'][$allowedDimensionKey]) && isset($nodeDataArray['dimensionValues'][$allowedDimensionKey][0])) {
                        $actualDimensionValue = $nodeDataArray['dimensionValues'][$allowedDimensionKey][0];
                        if (in_array($actualDimensionValue, $allowedDimensionValuesArray)) {
                            $foundValidDimensionValues = true;
                            break;
                        }
                    }
                }
            }

            if (!$foundValidDimensionValues) {
                $this->dispatch(self::EVENT_NOTICE, sprintf('Node <i>%s</i> has invalid dimension values: %s', $nodeDataArray['path'], json_encode($nodeDataArray['dimensionValues'])));
                $nodes[] = $nodeDataArray;
            }
        }
        return $nodes;
    }

    /**
     * Remove nodes with invalid workspace
     *
     * This removes nodes which refer to a workspace which does not exist.
     *
     * @return void
     */
    public function removeNodesWithInvalidWorkspace()
    {
        $this->dispatch(self::EVENT_NOTICE, 'Checking for nodes with invalid workspace ...');

        $nodesArray = $this->collectNodesWithInvalidWorkspace();
        if ($nodesArray === []) {
            return;
        }
        $numberOfNodes = count($nodesArray);
        $taskDescription = sprintf('Remove <i>%d</i> node%s referring to an invalid workspace.', $numberOfNodes, $numberOfNodes > 1 ? 's' : '');
        $taskClosure = function () use ($nodesArray) {
            foreach ($nodesArray as $nodeArray) {
                $this->removeNode($nodeArray['identifier'], $nodeArray['dimensionsHash']);
            }
        };
        $taskRequiresConfirmation = true;
        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure, $taskRequiresConfirmation);
    }

    /**
     * Collects all nodes of the given node type which refer to an invalid workspace
     * configuration.
     *
     * Note: due to the foreign key constraints in the database, there actually never should
     *       be any node with n.workspace of a non-existing workspace because if that workspace
     *       does not exist anymore, the value would turn NULL. But the query covers this nevertheless.
     *       Better safe than sorry.
     *
     * @return array
     */
    protected function collectNodesWithInvalidWorkspace()
    {
        $nodes = [];
        $workspaceNames = [];

        foreach ($this->workspaceRepository->findAll() as $workspace) {
            $workspaceNames[] = $workspace->getName();
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('n')
            ->from(NodeData::class, 'n')
            ->add(
                'where',
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->notIn('n.workspace', $workspaceNames),
                    $queryBuilder->expr()->isNull('n.workspace')
                )
            );

        foreach ($queryBuilder->getQuery()->getArrayResult() as $nodeDataArray) {
            $this->dispatch(self::EVENT_NOTICE, sprintf('Node <i>%s</i> (identifier: <i>%s</i>) refers to an invalid workspace: <i>%s</i>', $nodeDataArray['path'], $nodeDataArray['identifier'], (isset($nodeDataArray['workspace']) ? $nodeDataArray['workspace'] : 'null')));
            $nodes[] = $nodeDataArray;
        }
        return $nodes;
    }

    /**
     * Detect and fix nodes in non-live workspaces whose identifier does not match their corresponding node in the
     * live workspace.
     *
     * @return void
     */
    public function fixNodesWithInconsistentIdentifier()
    {
        $this->dispatch(self::EVENT_NOTICE, 'Checking for nodes with inconsistent identifier ...');

        $nodesArray = [];
        $liveWorkspaceNames = [];
        $nonLiveWorkspaceNames = [];
        foreach ($this->workspaceRepository->findAll() as $workspace) {
            /** @var Workspace $workspace */
            if ($workspace->getBaseWorkspace() !== null) {
                $nonLiveWorkspaceNames[] = $workspace->getName();
            } else {
                $liveWorkspaceNames[] = $workspace->getName();
            }
        }

        foreach ($nonLiveWorkspaceNames as $workspaceName) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $this->entityManager->createQueryBuilder();
            $queryBuilder->select('nonlive.Persistence_Object_Identifier, nonlive.identifier, nonlive.path, live.identifier AS liveIdentifier')
                ->from(NodeData::class, 'nonlive')
                ->join(NodeData::class, 'live', 'WITH', 'live.path = nonlive.path AND live.dimensionsHash = nonlive.dimensionsHash AND live.identifier != nonlive.identifier')
                ->where('nonlive.workspace = ?1')
                ->andWhere($queryBuilder->expr()->in('live.workspace', $liveWorkspaceNames))
                ->andWhere('nonlive.path != \'/\'')
                ->setParameter(1, $workspaceName)
            ;

            foreach ($queryBuilder->getQuery()->getArrayResult() as $nodeDataArray) {
                $this->dispatch(self::EVENT_NOTICE, sprintf('Node <i>%s</i> in workspace <i>%s</i> has identifier <i>%s</i> but live node has identifier <i>%s</i>.', $nodeDataArray['path'], $workspaceName, $nodeDataArray['identifier'], $nodeDataArray['liveIdentifier']));
                $nodesArray[] = $nodeDataArray;
            }
        }

        if ($nodesArray === []) {
            return;
        }

        $numberOfNodes = count($nodesArray);
        $taskDescription = sprintf('Fix identifier%s of %s node%s', $numberOfNodes > 1 ? 's' : '', $numberOfNodes, $numberOfNodes > 1 ? 's' : '');
        $taskClosure = function () use ($nodesArray) {
            foreach ($nodesArray as $nodeArray) {
                /** @var QueryBuilder $queryBuilder */
                $queryBuilder = $this->entityManager->createQueryBuilder();
                $queryBuilder->update(NodeData::class, 'nonlive')
                    ->set('nonlive.identifier', $queryBuilder->expr()->literal($nodeArray['liveIdentifier']))
                    ->where('nonlive.Persistence_Object_Identifier = ?1')
                    ->setParameter(1, $nodeArray['Persistence_Object_Identifier']);
                $result = $queryBuilder->getQuery()->getResult();
                if ($result !== 1) {
                    $errorMessage = 'The update query returned an unexpected result!' . PHP_EOL;
                    $errorMessage .= sprintf('<b>Query:</b> %s', $queryBuilder->getQuery()->getSQL()) . PHP_EOL;
                    $errorMessage .= sprintf('<b>Result:</b> %s', var_export($result, true));
                    $this->dispatch(self::EVENT_ERROR, $errorMessage);
                    return;
                }
            }
        };
        $taskRequiresConfirmation = true;
        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure, $taskRequiresConfirmation);
    }

    /**
     * Reorder child nodes according to the current position configuration of child nodes.
     *
     * @param string $workspaceName Name of the workspace to consider
     * @param boolean $dryRun Simulate?
     * @param NodeType $nodeType Only for this node type, if specified
     * @return void
     * @throws NodeConfigurationException
     * @throws NodeTypeNotFoundException
     */
    protected function reorderChildNodes($workspaceName, $dryRun, NodeType $nodeType = null)
    {
        if ($nodeType !== null) {
            $this->dispatch(self::EVENT_NOTICE, sprintf('Checking nodes of type "<i>%s</i>" for child nodes that need reordering ...', $nodeType));
            $this->reorderChildNodesByNodeType($workspaceName, $dryRun, $nodeType);
        } else {
            $this->dispatch(self::EVENT_NOTICE, 'Checking for child nodes that need reordering ...');
            foreach ($this->nodeTypeManager->getNodeTypes() as $nodeType) {
                /** @var NodeType $nodeType */
                if ($nodeType->isAbstract()) {
                    continue;
                }
                $this->reorderChildNodesByNodeType($workspaceName, $dryRun, $nodeType);
            }
        }

        $this->persistenceManager->persistAll();
    }

    /**
     * Reorder child nodes for the given node type
     *
     * @param string $workspaceName
     * @param boolean $dryRun (unused)
     * @param NodeType $nodeType
     * @return void
     * @throws NodeTypeNotFoundException
     * @throws NodeConfigurationException
     */
    protected function reorderChildNodesByNodeType($workspaceName, /** @noinspection PhpUnusedParameterInspection */$dryRun, NodeType $nodeType)
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), false);
        $nodeTypes[$nodeType->getName()] = $nodeType;

        if ($this->nodeTypeManager->hasNodeType((string)$nodeType)) {
            $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeType);
            $nodeTypeNames[$nodeType->getName()] = $nodeType;
        } else {
            $this->dispatch(self::EVENT_ERROR, sprintf('Node type "<i>%s</i>" does not exist', $nodeType));
            return;
        }

        /** @var $nodeType NodeType */
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            $childNodes = $nodeType->getAutoCreatedChildNodes();
            if ($childNodes === []) {
                continue;
            }

            foreach ($this->getNodeDataByNodeTypeAndWorkspace($nodeTypeName, $workspaceName) as $nodeData) {
                /** @var NodeInterface $childNodeBefore */
                $childNodeBefore = null;
                $context = $this->nodeFactory->createContextMatchingNodeData($nodeData);
                $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
                if (!$node instanceof NodeInterface) {
                    continue;
                }
                foreach ($childNodes as $childNodeName => $childNodeType) {
                    $childNode = $node->getNode($childNodeName);
                    if ($childNode) {
                        if ($childNodeBefore && $childNodeBefore->getIndex() >= $childNode->getIndex()) {
                            $taskDescription = sprintf('Move node named "<i>%s</i>" after node named "<i>%s</i>" in "<i>%s</i>"', $childNodeName, $childNodeBefore->getName(), $node->getPath());
                            $taskClosure = function () use ($childNode, $childNodeBefore) {
                                $childNode->moveAfter($childNodeBefore);
                            };
                            $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
                        }
                    } else {
                        $this->dispatch(self::EVENT_NOTICE, sprintf('Missing child node named "<i>%s</i>" in "<i>%s</i>".', $childNodeName, $node->getPath()));
                    }

                    $childNodeBefore = $childNode;
                }
            }
        }
    }

    /**
     * Repair nodes whose shadow nodes are missing
     *
     * This check searches for nodes which have a corresponding node in one of the base workspaces,
     * have different node paths, but don't have a corresponding shadow node with a "movedto" value.
     *
     * @param string $workspaceName Currently ignored
     * @param boolean $dryRun (unused)
     * @param NodeType $nodeType This argument will be ignored
     * @return void
     */
    protected function repairShadowNodes($workspaceName, /** @noinspection PhpUnusedParameterInspection */$dryRun, NodeType $nodeType = null)
    {
        /** @var Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        if ($workspace->getBaseWorkspace() === null) {
            $this->dispatch(self::EVENT_NOTICE, sprintf('Repairing base workspace "<i>%s</i>", therefore skipping check for shadow nodes.', $workspaceName));
            return;
        }

        $this->dispatch(self::EVENT_NOTICE, 'Checking for nodes with missing shadow nodes ...');
        $newShadowNodes = $this->findMissingShadowNodesInWorkspace($workspace, $nodeType);
        if ($newShadowNodes === []) {
            return;
        }
        $numberOfNewShadowNodes = count($newShadowNodes);

        $taskDescription = sprintf('Add <i>%d</i> missing shadow node%s', $numberOfNewShadowNodes, $numberOfNewShadowNodes > 1 ? 's' : '');
        $taskClosure = function () use ($newShadowNodes) {
            /** @var NodeData $nodeData */
            foreach ($newShadowNodes as list('nodeData' => $nodeData, 'shadowPath' => $shadowPath)) {
                $nodeData->createShadow($shadowPath);
            }
            $this->persistenceManager->persistAll();
        };
        $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
    }

    /**
     * Collects all nodes with missing shadow nodes
     *
     * @param Workspace $workspace
     * @param NodeType $nodeType
     * @return array in the form [['nodeData' => <nodeDataInstance>, 'shadowPath' => '<shadowPath>'], ...]
     */
    protected function findMissingShadowNodesInWorkspace(Workspace $workspace, NodeType $nodeType = null)
    {
        $workspaces = array_merge([$workspace], $workspace->getBaseWorkspaces());

        $newShadowNodes = [];
        foreach ($workspaces as $workspace) {
            /** @var Workspace $workspace */
            if ($workspace->getBaseWorkspace() === null) {
                continue;
            }

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $this->entityManager->createQueryBuilder();
            $queryBuilder->select('n')
                ->from(NodeData::class, 'n')
                ->where('n.workspace = :workspace');
            $queryBuilder->setParameter('workspace', $workspace->getName());
            if ($nodeType !== null) {
                $queryBuilder->andWhere('n.nodeType = :nodeType');
                $queryBuilder->setParameter('nodeType', $nodeType->getName());
            }

            /** @var NodeData $nodeData */
            foreach ($queryBuilder->getQuery()->getResult() as $nodeData) {
                $nodeDataSeenFromParentWorkspace = $this->nodeDataRepository->findOneByIdentifier($nodeData->getIdentifier(), $workspace->getBaseWorkspace(), $nodeData->getDimensionValues());
                // This is the good case, either the node does not exist or was shadowed
                if ($nodeDataSeenFromParentWorkspace === null) {
                    continue;
                }
                // Also good, the node was not moved at all.
                if ($nodeDataSeenFromParentWorkspace->getPath() === $nodeData->getPath()) {
                    continue;
                }

                $nodeDataOnSamePath = $this->nodeDataRepository->findOneByPath($nodeData->getPath(), $workspace->getBaseWorkspace(), $nodeData->getDimensionValues(), null);
                // We cannot just put a shadow node in the path, something exists, but that should be fine.
                if ($nodeDataOnSamePath !== null) {
                    continue;
                }
                $newShadowNodes[] = ['nodeData' => $nodeData, 'shadowPath' => $nodeDataSeenFromParentWorkspace->getPath()];
            }
        }

        return $newShadowNodes;
    }

    /**
     * Attaches a new event handler
     *
     * @param string $eventIdentifier one of the EVENT_* constants
     * @param \Closure $callback a closure to be invoked when the corresponding event was triggered
     * @return void
     */
    public function on(string $eventIdentifier, \Closure $callback): void
    {
        $this->eventCallbacks[$eventIdentifier][] = $callback;
    }

    /**
     * Trigger a custom event
     *
     * @param string $eventIdentifier one of the EVENT_* constants
     * @param array $eventPayload optional arguments to be passed to the handler closure
     * @return void
     */
    protected function dispatch(string $eventIdentifier, ...$eventPayload): void
    {
        if (!isset($this->eventCallbacks[$eventIdentifier])) {
            return;
        }
        /** @var \Closure $callback */
        foreach ($this->eventCallbacks[$eventIdentifier] as $callback) {
            call_user_func_array($callback, $eventPayload);
        }
    }
}
