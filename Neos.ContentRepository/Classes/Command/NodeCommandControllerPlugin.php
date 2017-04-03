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

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\QueryBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
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
class NodeCommandControllerPlugin implements NodeCommandControllerPluginInterface
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
     */
    protected $output;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
     * interface ...
     *
     * @Flow\Inject
     * @var \Doctrine\Common\Persistence\ObjectManager
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
    protected $pluginConfigurations = array();

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
    }

    /**
     * A method which runs the task implemented by the plugin for the given command
     *
     * @param string $controllerCommandName Name of the command in question, for example "repair"
     * @param ConsoleOutput $output An instance of ConsoleOutput which can be used for output or dialogues
     * @param NodeType $nodeType Only handle this node type (if specified)
     * @param string $workspaceName Only handle this workspace (if specified)
     * @param boolean $dryRun If TRUE, don't do any changes, just simulate what you would do
     * @param boolean $cleanup If FALSE, cleanup tasks are skipped
     * @param string $skip Skip the given check or checks (comma separated)
     * @param string $only Only execute the given check or checks (comma separated)
     * @return void
     */
    public function invokeSubCommand($controllerCommandName, ConsoleOutput $output, NodeType $nodeType = null, $workspaceName = 'live', $dryRun = false, $cleanup = true, $skip = null, $only = null)
    {
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
     * @param string $question
     * @param \Closure $task
     * @return void
     */
    protected function askBeforeExecutingTask($question, \Closure $task)
    {
        $response = null;
        while (!in_array($response, array('y', 'n'))) {
            $response = strtolower($this->output->ask('<comment>' . $question . ' (y/n)</comment>'));
        }
        $this->output->outputLine();

        switch ($response) {
            case 'y':
                $task();
                break;
            case 'n':
                $this->output->outputLine('Skipping.');
                break;
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
     */
    protected function createMissingChildNodes($workspaceName, $dryRun, NodeType $nodeType = null)
    {
        if ($nodeType !== null) {
            $this->output->outputLine('Checking nodes of type "%s" for missing child nodes ...', array($nodeType->getName()));
            $this->createChildNodesByNodeType($nodeType, $workspaceName, $dryRun);
        } else {
            $this->output->outputLine('Checking for missing child nodes ...');
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
     */
    protected function createChildNodesByNodeType(NodeType $nodeType, $workspaceName, $dryRun)
    {
        $createdNodesCount = 0;
        $updatedNodesCount = 0;
        $nodeCreationExceptions = 0;

        $nodeIdentifiersWhichNeedUpdate = [];

        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), false);
        $nodeTypes[$nodeType->getName()] = $nodeType;

        if ($this->nodeTypeManager->hasNodeType((string)$nodeType)) {
            $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeType);
            $nodeTypeNames[$nodeType->getName()] = $nodeType;
        } else {
            $this->output->outputLine('Node type "%s" does not exist', array((string)$nodeType));
            exit(1);
        }

        /** @var $nodeType NodeType */
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            $childNodes = $nodeType->getAutoCreatedChildNodes();
            foreach ($this->getNodeDataByNodeTypeAndWorkspace($nodeTypeName, $workspaceName) as $nodeData) {
                $context = $this->nodeFactory->createContextMatchingNodeData($nodeData);
                $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
                if (!$node instanceof NodeInterface) {
                    continue;
                }
                foreach ($childNodes as $childNodeName => $childNodeType) {
                    try {
                        $childNode = $node->getNode($childNodeName);
                        $childNodeIdentifier = Utility::buildAutoCreatedChildNodeIdentifier($childNodeName, $node->getIdentifier());
                        if ($childNode === null) {
                            if ($dryRun === false) {
                                $node->createNode($childNodeName, $childNodeType, $childNodeIdentifier);
                                $this->output->outputLine('Auto created node named "%s" in "%s"', array($childNodeName, $node->getPath()));
                            } else {
                                $this->output->outputLine('Missing node named "%s" in "%s"', array($childNodeName, $node->getPath()));
                            }
                            $createdNodesCount++;
                        } elseif ($childNode->getIdentifier() !== $childNodeIdentifier) {
                            $nodeIdentifiersWhichNeedUpdate[$childNode->getIdentifier()] = $childNodeIdentifier;
                        }
                    } catch (\Exception $exception) {
                        $this->output->outputLine('Could not create node named "%s" in "%s" (%s)', array($childNodeName, $node->getPath(), $exception->getMessage()));
                        $nodeCreationExceptions++;
                    }
                }
            }
        }

        if (count($nodeIdentifiersWhichNeedUpdate) > 0) {
            if ($dryRun === false) {
                foreach ($nodeIdentifiersWhichNeedUpdate as $oldNodeIdentifier => $newNodeIdentifier) {
                    $queryBuilder = $this->entityManager->createQueryBuilder();
                    $queryBuilder->update(NodeData::class, 'n')
                        ->set('n.identifier', $queryBuilder->expr()->literal($newNodeIdentifier))
                        ->where('n.identifier = ?1')
                        ->setParameter(1, $oldNodeIdentifier);
                    $result = $queryBuilder->getQuery()->getResult();
                    $updatedNodesCount++;
                    $this->output->outputLine('Updated node identifier from %s to %s because it was not a "stable" identifier', [ $oldNodeIdentifier, $newNodeIdentifier ]);
                }
            } else {
                foreach ($nodeIdentifiersWhichNeedUpdate as $oldNodeIdentifier => $newNodeIdentifier) {
                    $this->output->outputLine('Child nodes with identifier "%s" need to change their identifier to "%s"', [ $oldNodeIdentifier, $newNodeIdentifier ]);
                    $updatedNodesCount++;
                }
            }
        }

        if ($createdNodesCount !== 0 || $nodeCreationExceptions !== 0 || $updatedNodesCount !== 0) {
            if ($dryRun === false) {
                if ($createdNodesCount > 0) {
                    $this->output->outputLine('Created %s new child nodes', array($createdNodesCount));
                }
                if ($updatedNodesCount > 0) {
                    $this->output->outputLine('Updated identifier of %s child nodes', array($updatedNodesCount));
                }
                if ($nodeCreationExceptions > 0) {
                    $this->output->outputLine('%s Errors occurred during child node creation', array($nodeCreationExceptions));
                }
                $this->persistenceManager->persistAll();
            } else {
                if ($createdNodesCount > 0) {
                    $this->output->outputLine('%s missing child nodes need to be created', array($createdNodesCount));
                }
                if ($updatedNodesCount > 0) {
                    $this->output->outputLine('%s identifiers of child nodes need to be updated', array($updatedNodesCount));
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
     */
    public function addMissingDefaultValues($workspaceName, $dryRun, NodeType $nodeType = null)
    {
        if ($nodeType !== null) {
            $this->output->outputLine('Checking nodes of type "%s" for missing default values ...', array($nodeType->getName()));
            $this->addMissingDefaultValuesByNodeType($nodeType, $workspaceName, $dryRun);
        } else {
            $this->output->outputLine('Checking for missing default values ...');
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
     */
    public function addMissingDefaultValuesByNodeType(NodeType $nodeType = null, $workspaceName, $dryRun)
    {
        $addedMissingDefaultValuesCount = 0;

        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), false);
        $nodeTypes[$nodeType->getName()] = $nodeType;

        if ($this->nodeTypeManager->hasNodeType((string)$nodeType)) {
            $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeType);
            $nodeTypeNames[$nodeType->getName()] = $nodeType;
        } else {
            $this->output->outputLine('Node type "%s" does not exist', array((string)$nodeType));
            exit(1);
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
                        $this->output->outputLine('Skipping node %s because it has no dimension values set', [$node->getPath()]);
                    } else {
                        $this->output->outputLine('Skipping node %s because it has invalid dimension values: %s', [$node->getPath(), json_encode($node->getNodeData()->getDimensionValues())]);
                    }
                    continue;
                }

                foreach ($defaultValues as $propertyName => $defaultValue) {
                    if ($propertyName[0] === '_') {
                        continue;
                    }

                    if (!$node->hasProperty($propertyName)) {
                        $addedMissingDefaultValuesCount++;
                        if (!$dryRun) {
                            $node->setProperty($propertyName, $defaultValue);
                            $this->output->outputLine('Set default value for property named "%s" in "%s" (%s)', array($propertyName, $node->getPath(), $node->getNodeType()->getName()));
                        } else {
                            $this->output->outputLine('Found missing default value for property named "%s" in "%s" (%s)', array($propertyName, $node->getPath(), $node->getNodeType()->getName()));
                        }
                    }
                }
            }
        }

        if ($addedMissingDefaultValuesCount !== 0) {
            if ($dryRun === false) {
                $this->persistenceManager->persistAll();
                $this->output->outputLine('Added %s new default values', array($addedMissingDefaultValuesCount));
            } else {
                $this->output->outputLine('%s missing default values need to be set', array($addedMissingDefaultValuesCount));
            }
        }
    }

    /**
     * Performs checks for nodes with abstract or undefined node types and removes them if found.
     *
     * @param string $workspaceName
     * @param boolean $dryRun Simulate?
     * @return void
     */
    protected function removeAbstractAndUndefinedNodes($workspaceName, $dryRun)
    {
        $this->output->outputLine('Checking for nodes with abstract or undefined node types ...');

        $abstractNodeTypes = array();
        $nonAbstractNodeTypes = array();
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
            $this->output->outputLine('Found node with %s node type named "%s" (%s) in "%s"', array($type, $name, $node['nodeType'], $node['path']));
        }

        $this->output->outputLine();
        if (!$dryRun) {
            $self = $this;
            $this->askBeforeExecutingTask('Abstract or undefined node types found, do you want to remove them?', function () use ($self, $nodes, $workspaceName, $removableNodesCount) {
                foreach ($nodes as $node) {
                    $self->removeNode($node['identifier'], $node['dimensionsHash']);
                }
                $self->output->outputLine('Removed %s node%s with abstract or undefined node types.', array($removableNodesCount, $removableNodesCount > 1 ? 's' : ''));
            });
        } else {
            $this->output->outputLine('Found %s node%s with abstract or undefined node types to be removed.', array($removableNodesCount, $removableNodesCount > 1 ? 's' : ''));
        }
        $this->output->outputLine();
    }

    /**
     * Performs checks for disallowed child nodes according to the node's auto-create configuration and constraints
     * and removes them if found.
     *
     * @param string $workspaceName
     * @param boolean $dryRun Simulate?
     * @return void
     */
    protected function removeDisallowedChildNodes($workspaceName, $dryRun)
    {
        $this->output->outputLine('Checking for disallowed child nodes ...');

        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        /** @var \Neos\ContentRepository\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);

        $nodes = array();
        $nodeExceptionCount = 0;
        $removeDisallowedChildNodes = function (NodeInterface $node) use (&$removeDisallowedChildNodes, &$nodes, &$nodeExceptionCount, $queryBuilder) {
            try {
                foreach ($node->getChildNodes() as $childNode) {
                    /** @var $childNode NodeInterface */
                    if (!$childNode->isAutoCreated() && !$node->isNodeTypeAllowedAsChildNode($childNode->getNodeType())) {
                        $nodes[] = $childNode;
                        $parent = $node->isAutoCreated() ? $node->getParent() : $node;
                        $this->output->outputLine('Found disallowed node named "%s" (%s) in "%s", child of node "%s" (%s)', array($childNode->getName(), $childNode->getNodeType()->getName(), $childNode->getPath(), $parent->getName(), $parent->getNodeType()->getName()));
                    } else {
                        $removeDisallowedChildNodes($childNode);
                    }
                }
            } catch (\Exception $e) {
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
            $this->output->outputLine();
            if (!$dryRun) {
                $self = $this;
                $this->askBeforeExecutingTask('Do you want to remove all disallowed child nodes?', function () use ($self, $nodes, $disallowedChildNodesCount, $workspaceName) {
                    foreach ($nodes as $node) {
                        $self->removeNodeAndChildNodesInWorkspaceByPath($node->getPath(), $workspaceName);
                    }
                    $self->output->outputLine('Removed %s disallowed node%s.', array($disallowedChildNodesCount, $disallowedChildNodesCount > 1 ? 's' : ''));
                });
            } else {
                $this->output->outputLine('Found %s disallowed node%s to be removed.', array($disallowedChildNodesCount, $disallowedChildNodesCount > 1 ? 's' : ''));
            }

            if ($nodeExceptionCount > 0) {
                $this->output->outputLine();
                $this->output->outputLine('%s error%s occurred during child node traversing.', array($nodeExceptionCount, $nodeExceptionCount > 1 ? 's' : ''));
            }
            $this->output->outputLine();
        }
    }

    /**
     * Performs checks for orphan nodes removes them if found.
     *
     * @param string $workspaceName
     * @param boolean $dryRun Simulate?
     * @return void
     */
    protected function removeOrphanNodes($workspaceName, $dryRun)
    {
        $this->output->outputLine('Checking for orphan nodes ...');

        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $workspaceList = array();
        /** @var \Neos\ContentRepository\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        while ($workspace !== null) {
            $workspaceList[] = $workspace->getName();
            $workspace = $workspace->getBaseWorkspace();
        }

        $nodes = $queryBuilder
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
            ->andWhere('n.workspace = :workspace')
            ->setParameters(array('workspaceList' => $workspaceList, 'slash' => '/', 'workspace' => $workspaceName))
            ->getQuery()->getArrayResult();

        $nodesToBeRemoved = count($nodes);
        if ($nodesToBeRemoved === 0) {
            return;
        }

        foreach ($nodes as $node) {
            $name = $node['path'] === '/' ? '' : substr($node['path'], strrpos($node['path'], '/') + 1);
            $this->output->outputLine('Found orphan node named "%s" (%s) in "%s"', array($name, $node['nodeType'], $node['path']));
        }

        $this->output->outputLine();
        if (!$dryRun) {
            $self = $this;
            $this->askBeforeExecutingTask('Do you want to remove all orphan nodes?', function () use ($self, $nodes, $workspaceName, $nodesToBeRemoved) {
                foreach ($nodes as $node) {
                    $self->removeNodeAndChildNodesInWorkspaceByPath($node['path'], $workspaceName);
                }
                $self->output->outputLine('Removed %s orphan node%s.', array($nodesToBeRemoved, count($nodes) > 1 ? 's' : ''));
            });
        } else {
            $this->output->outputLine('Found %s orphan node%s to be removed.', array($nodesToBeRemoved, count($nodes) > 1 ? 's' : ''));
        }
        $this->output->outputLine();
    }

    /**
     * Performs checks for orphan nodes removes them if found.
     *
     * @param string $workspaceName
     * @param boolean $dryRun Simulate?
     * @param NodeType $nodeType Only for this node type, if specified
     * @return void
     */
    public function removeUndefinedProperties($workspaceName, $dryRun, NodeType $nodeType = null)
    {
        $this->output->outputLine('Checking for undefined properties ...');

        /** @var \Neos\ContentRepository\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);

        $nodesWithUndefinedPropertiesNodes = array();
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
                $undefinedProperties = array_diff(array_keys($node->getProperties()), array_keys($nodeType->getProperties()));
                if ($undefinedProperties !== array()) {
                    $nodesWithUndefinedPropertiesNodes[$node->getIdentifier()] = array('node' => $node, 'undefinedProperties' => $undefinedProperties);
                    foreach ($undefinedProperties as $undefinedProperty) {
                        $undefinedPropertiesCount++;
                        $this->output->outputLine('Found undefined property named "%s" in "%s" (%s)', array($undefinedProperty, $node->getPath(), $node->getNodeType()->getName()));
                    }
                }
            } catch (NodeTypeNotFoundException $exception) {
                $this->output->outputLine('Skipped undefined node type in "%s"', array($nodeData->getPath()));
            }
        }

        if ($undefinedPropertiesCount > 0) {
            $this->output->outputLine();
            if (!$dryRun) {
                $self = $this;
                $this->askBeforeExecutingTask('Do you want to remove undefined node properties?', function () use ($self, $nodesWithUndefinedPropertiesNodes, $undefinedPropertiesCount, $workspaceName, $dryRun) {
                    foreach ($nodesWithUndefinedPropertiesNodes as $nodesWithUndefinedPropertiesNode) {
                        /** @var NodeInterface $node */
                        $node = $nodesWithUndefinedPropertiesNode['node'];
                        foreach ($nodesWithUndefinedPropertiesNode['undefinedProperties'] as $undefinedProperty) {
                            if ($node->hasProperty($undefinedProperty)) {
                                $node->removeProperty($undefinedProperty);
                            }
                        }
                    }
                    $self->output->outputLine('Removed %s undefined propert%s.', array($undefinedPropertiesCount, $undefinedPropertiesCount > 1 ? 'ies' : 'y'));
                });
            } else {
                $this->output->outputLine('Found %s undefined propert%s to be removed.', array($undefinedPropertiesCount, $undefinedPropertiesCount > 1 ? 'ies' : 'y'));
            }
            $this->output->outputLine();
        }

        $this->persistenceManager->persistAll();
    }

    /**
     * Remove broken entity references
     *
     * This removes references from nodes to entities which don't exist anymore.
     *
     * @param string $workspaceName
     * @param boolean $dryRun
     * @return void
     */
    public function removeBrokenEntityReferences($workspaceName, $dryRun)
    {
        $this->output->outputLine('Checking for broken entity references ...');

        /** @var \Neos\ContentRepository\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);

        $nodeTypesWithEntityReferences = array();
        foreach ($this->nodeTypeManager->getNodeTypes() as $nodeType) {
            /** @var NodeType $nodeType */
            foreach (array_keys($nodeType->getProperties()) as $propertyName) {
                $propertyType = $nodeType->getPropertyType($propertyName);
                if (strpos($propertyType, '\\') !== false) {
                    if (!isset($nodeTypesWithEntityReferences[$nodeType->getName()])) {
                        $nodeTypesWithEntityReferences[$nodeType->getName()] = array();
                    }
                    $nodeTypesWithEntityReferences[$nodeType->getName()][$propertyName] = $propertyType;
                }
            }
        }

        $nodesWithBrokenEntityReferences = array();
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
                            $this->output->outputLine('Broken reference in "%s", property "%s" (%s) referring to %s.', array($nodeData->getPath(), $nodeData->getIdentifier(), $propertyName, $propertyType, $propertyValue));
                            $brokenReferencesCount ++;
                        }
                    }
                    if ($convertedProperty instanceof Proxy) {
                        try {
                            $convertedProperty->__load();
                        } catch (EntityNotFoundException $e) {
                            $nodesWithBrokenEntityReferences[$nodeData->getIdentifier()][$propertyName] = $nodeData;
                            $this->output->outputLine('Broken reference in "%s", property "%s" (%s) referring to %s.', array($nodeData->getPath(), $nodeData->getIdentifier(), $propertyName, $propertyType, $propertyValue));
                            $brokenReferencesCount ++;
                        }
                    }
                }
            }
        }

        if ($brokenReferencesCount > 0) {
            $this->output->outputLine();
            if (!$dryRun) {
                $self = $this;
                $this->askBeforeExecutingTask('Do you want to remove the broken entity references?', function () use ($self, $nodesWithBrokenEntityReferences, $brokenReferencesCount, $workspaceName, $dryRun) {
                    foreach ($nodesWithBrokenEntityReferences as $nodeIdentifier => $properties) {
                        foreach ($properties as $propertyName => $nodeData) {
                            /** @var NodeData $nodeData */
                            $nodeData->setProperty($propertyName, null);
                        }
                    }
                    $self->output->outputLine('Removed %s broken entity reference%s.', array($brokenReferencesCount, $brokenReferencesCount > 1 ? 's' : ''));
                });
            } else {
                $this->output->outputLine('Found %s broken entity reference%s to be removed.', array($brokenReferencesCount, $brokenReferencesCount > 1 ? 's' : ''));
            }
            $this->output->outputLine();

            $this->persistenceManager->persistAll();
        }
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
        return $this->contextFactory->create(array(
            'workspaceName' => $workspaceName,
            'dimensions' => $dimensions,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true,
            'removedContentShown' => true
        ));
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
            ->setParameters(array('path' => $nodePath, 'subpath' => $nodePath . '/%', 'workspace' => $workspaceName))
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
            ->setParameters(array('identifier' => $nodeIdentifier, 'dimensionsHash' => $dimensionsHash))
            ->getQuery()
            ->execute();
    }

    /**
     * Remove nodes with invalid dimension values
     *
     * This removes nodes which have dimension values not fitting to the current dimension configuration
     *
     * @param string $workspaceName Name of the workspace to consider
     * @param boolean $dryRun Simulate?
     * @return void
     */
    public function removeNodesWithInvalidDimensions($workspaceName, $dryRun)
    {
        $this->output->outputLine('Checking for nodes with invalid dimensions ...');

        $allowedDimensionCombinations = $this->contentDimensionCombinator->getAllAllowedCombinations();
        $nodesArray = $this->collectNodesWithInvalidDimensions($workspaceName, $allowedDimensionCombinations);
        if ($nodesArray === []) {
            return;
        }

        if (!$dryRun) {
            $self = $this;
            $this->output->outputLine();
            $this->output->outputLine('Nodes with invalid dimension values found.' . PHP_EOL . 'You might solve this by migrating them to your current dimension configuration or by removing them.');
            $this->askBeforeExecutingTask(sprintf('Do you want to remove %s node%s with invalid dimensions now?', count($nodesArray), count($nodesArray) > 1 ? 's' : ''), function () use ($self, $nodesArray, $workspaceName) {
                foreach ($nodesArray as $nodeArray) {
                    $self->removeNode($nodeArray['identifier'], $nodeArray['dimensionsHash']);
                }
                $self->output->outputLine('Removed %s node%s with invalid dimension values.', array(count($nodesArray), count($nodesArray) > 1 ? 's' : ''));
            });
        } else {
            $this->output->outputLine('Found %s node%s with invalid dimension values to be removed.', array(count($nodesArray), count($nodesArray) > 1 ? 's' : ''));
        }
        $this->output->outputLine();
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
                $this->output->outputLine('Node %s has invalid dimension values: %s', [$nodeDataArray['path'], json_encode($nodeDataArray['dimensionValues'])]);
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
     * @param string $workspaceName This argument will be ignored
     * @param boolean $dryRun Simulate?
     * @return void
     */
    public function removeNodesWithInvalidWorkspace($workspaceName, $dryRun)
    {
        $this->output->outputLine('Checking for nodes with invalid workspace ...');

        $nodesArray = $this->collectNodesWithInvalidWorkspace();
        if ($nodesArray === []) {
            return;
        }

        if (!$dryRun) {
            $self = $this;
            $this->output->outputLine();
            $this->output->outputLine('Nodes with invalid workspace found.');
            $this->askBeforeExecutingTask(sprintf('Do you want to remove %s node%s with invalid workspaces now?', count($nodesArray), count($nodesArray) > 1 ? 's' : ''), function () use ($self, $nodesArray) {
                foreach ($nodesArray as $nodeArray) {
                    $self->removeNode($nodeArray['identifier'], $nodeArray['dimensionsHash']);
                }
                $self->output->outputLine('Removed %s node%s referring to an invalid workspace.', array(count($nodesArray), count($nodesArray) > 1 ? 's' : ''));
            });
        } else {
            $this->output->outputLine('Found %s node%s referring to an invalid workspace to be removed.', array(count($nodesArray), count($nodesArray) > 1 ? 's' : ''));
        }
        $this->output->outputLine();
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
            ->add('where', $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->notIn('n.workspace', $workspaceNames),
                    $queryBuilder->expr()->isNull('n.workspace')
                )
            );

        foreach ($queryBuilder->getQuery()->getArrayResult() as $nodeDataArray) {
            $this->output->outputLine('Node %s (identifier: %s) refers to an invalid workspace: %s', [$nodeDataArray['path'], $nodeDataArray['identifier'], (isset($nodeDataArray['workspace']) ? $nodeDataArray['workspace'] : 'null')]);
            $nodes[] = $nodeDataArray;
        }
        return $nodes;
    }

    /**
     * Detect and fix nodes in non-live workspaces whose identifier does not match their corresponding node in the
     * live workspace.
     *
     * @param string $workspaceName This argument will be ignored
     * @param boolean $dryRun Simulate?
     * @return void
     */
    public function fixNodesWithInconsistentIdentifier($workspaceName, $dryRun)
    {
        $this->output->outputLine('Checking for nodes with inconsistent identifier ...');

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
                $this->output->outputLine('Node %s in workspace %s has identifier %s but live node has identifier %s.', [$nodeDataArray['path'], $workspaceName, $nodeDataArray['identifier'], $nodeDataArray['liveIdentifier']]);
                $nodesArray[] = $nodeDataArray;
            }
        }

        if ($nodesArray === []) {
            return;
        }

        if (!$dryRun) {
            $self = $this;
            $this->output->outputLine();
            $this->output->outputLine('Nodes with inconsistent identifiers found.');
            $this->askBeforeExecutingTask(sprintf('Do you want to fix the identifiers of %s node%s now?', count($nodesArray), count($nodesArray) > 1 ? 's' : ''), function () use ($self, $nodesArray) {
                foreach ($nodesArray as $nodeArray) {
                    /** @var QueryBuilder $queryBuilder */
                    $queryBuilder = $this->entityManager->createQueryBuilder();
                    $queryBuilder->update(NodeData::class, 'nonlive')
                        ->set('nonlive.identifier', $queryBuilder->expr()->literal($nodeArray['liveIdentifier']))
                        ->where('nonlive.Persistence_Object_Identifier = ?1')
                        ->setParameter(1, $nodeArray['Persistence_Object_Identifier']);
                    $result = $queryBuilder->getQuery()->getResult();
                    if ($result !== 1) {
                        $self->output->outputLine('<error>Error:</error> The update query returned an unexpected result!');
                        $self->output->outputLine('<b>Query:</b> ' . $queryBuilder->getQuery()->getSQL());
                        $self->output->outputLine('<b>Result:</b> %s', [ var_export($result, true)]);
                        exit(1);
                    }
                }
                $self->output->outputLine('Fixed inconsistent identifiers.');
            });
        } else {
            $this->output->outputLine('Found %s node%s with inconsistent identifiers which need to be fixed.', array(count($nodesArray), count($nodesArray) > 1 ? 's' : ''));
        }
        $this->output->outputLine();
    }

    /**
     * Reorder child nodes according to the current position configuration of child nodes.
     *
     * @param string $workspaceName Name of the workspace to consider
     * @param boolean $dryRun Simulate?
     * @param NodeType $nodeType Only for this node type, if specified
     * @return void
     */
    protected function reorderChildNodes($workspaceName, $dryRun, NodeType $nodeType = null)
    {
        if ($nodeType !== null) {
            $this->output->outputLine('Checking nodes of type "%s" for child nodes that need reordering ...', array($nodeType->getName()));
            $this->reorderChildNodesByNodeType($workspaceName, $dryRun, $nodeType);
        } else {
            $this->output->outputLine('Checking for child nodes that need reordering ...');
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
     * @param boolean $dryRun
     * @param NodeType $nodeType
     * @return void
     */
    protected function reorderChildNodesByNodeType($workspaceName, $dryRun, NodeType $nodeType)
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), false);
        $nodeTypes[$nodeType->getName()] = $nodeType;

        if ($this->nodeTypeManager->hasNodeType((string)$nodeType)) {
            $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeType);
            $nodeTypeNames[$nodeType->getName()] = $nodeType;
        } else {
            $this->output->outputLine('Node type "%s" does not exist', array((string)$nodeType));
            exit(1);
        }

        /** @var $nodeType NodeType */
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            $childNodes = $nodeType->getAutoCreatedChildNodes();
            if ($childNodes === array()) {
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
                        if ($childNodeBefore) {
                            if ($dryRun === false) {
                                if ($childNodeBefore->getIndex() >= $childNode->getIndex()) {
                                    $childNode->moveAfter($childNodeBefore);
                                    $this->output->outputLine('Moved node named "%s" after node named "%s" in "%s"', array($childNodeName, $childNodeBefore->getName(), $node->getPath()));
                                }
                            } else {
                                $this->output->outputLine('Should move node named "%s" after node named "%s" in "%s"', array($childNodeName, $childNodeBefore->getName(), $node->getPath()));
                            }
                        }
                    } else {
                        $this->output->outputLine('Missing child node named "%s" in "%s".', array($childNodeName, $node->getPath()));
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
     * @param boolean $dryRun Simulate?
     * @param NodeType $nodeType This argument will be ignored
     * @return void
     */
    protected function repairShadowNodes($workspaceName, $dryRun, NodeType $nodeType = null)
    {
        /** @var Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        if ($workspace->getBaseWorkspace() === null) {
            $this->output->outputLine('Repairing base workspace "%s", therefore skipping check for shadow nodes.', [$workspaceName]);
            $this->output->outputLine();
            return;
        }

        $this->output->outputLine('Checking for nodes with missing shadow nodes ...');
        $fixedShadowNodes = $this->fixShadowNodesInWorkspace($workspace, $nodeType);

        $this->output->outputLine('%s %s node%s with missing shadow nodes.', [
            $dryRun ? 'Would repair' : 'Repaired',
            $fixedShadowNodes,
            $fixedShadowNodes !== 1 ? 's' : ''
        ]);

        $this->output->outputLine();
    }

    /**
     * Collects all nodes with missing shadow nodes
     *
     * @param Workspace $workspace
     * @param boolean $dryRun
     * @param NodeType $nodeType
     * @return array
     */
    protected function fixShadowNodesInWorkspace(Workspace $workspace, $dryRun, NodeType $nodeType = null)
    {
        $workspaces = array_merge([$workspace], $workspace->getBaseWorkspaces());

        $fixedShadowNodes = 0;
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

                if (!$dryRun) {
                    $nodeData->createShadow($nodeDataSeenFromParentWorkspace->getPath());
                }
                $fixedShadowNodes++;
            }
        }

        return $fixedShadowNodes;
    }
}
