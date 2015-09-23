<?php
namespace TYPO3\TYPO3CR\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\QueryBuilder;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\ConsoleOutput;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContentDimensionCombinator;
use TYPO3\TYPO3CR\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException;

/**
 * Plugin for the TYPO3CR NodeCommandController which provides functionality for creating missing child nodes.
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
     * @var array
     */
    protected $pluginConfigurations = array();

    /**
     * @var ContentDimensionCombinator
     * @Flow\Inject
     */
    protected $contentDimensionCombinator;

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
                return 'Run several operations to ensure the node integrity';
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
                return
                    '<u>Remove abstract and undefined node types</u>' . PHP_EOL .
                    PHP_EOL .
                    'Will remove all nodes that has an abstract or undefined node type.' . PHP_EOL . PHP_EOL .
                    '<u>Remove orphan (parentless) nodes</u>' . PHP_EOL .
                    PHP_EOL .
                    'Will remove all child nodes that do not have a connection to the root node.' . PHP_EOL . PHP_EOL .
                    '<u>Remove disallowed child nodes</u>' . PHP_EOL .
                    PHP_EOL .
                    'Will remove all child nodes that are disallowed according to the node types\' auto-create' . PHP_EOL .
                    'configuration and constraints.' . PHP_EOL . PHP_EOL .
                    '<u>Remove undefined node properties</u>' . PHP_EOL .
                    PHP_EOL .
                    'Will remove all undefined properties according to the node type configuration.' . PHP_EOL .
                    '<u>Missing child nodes</u>' . PHP_EOL .
                    PHP_EOL .
                    'For all nodes (or only those which match the --node-type filter specified with this' . PHP_EOL .
                    'command) which currently don\'t have child nodes as configured by the node type\'s' . PHP_EOL .
                    'configuration new child nodes will be created.' . PHP_EOL . PHP_EOL .
                    '<u>Reorder child nodes</u>' . PHP_EOL .
                    PHP_EOL .
                    'For all nodes (or only those which match the --node-type filter specified with this' . PHP_EOL .
                    'command) which have configured child nodes, those child nodes are reordered according to the' . PHP_EOL .
                    'position from the parents NodeType configuration.' . PHP_EOL . PHP_EOL .
                    '<u>Missing default properties</u>' . PHP_EOL .
                    PHP_EOL .
                    'For all nodes (or only those which match the --node-type filter specified with this' . PHP_EOL .
                    'command) which currently don\'t have a property that have a default value configuration' . PHP_EOL .
                    'the default value for that property will be set.' . PHP_EOL;
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
     * @return void
     */
    public function invokeSubCommand($controllerCommandName, ConsoleOutput $output, NodeType $nodeType = null, $workspaceName = 'live', $dryRun = false, $cleanup = true)
    {
        $this->output = $output;
        switch ($controllerCommandName) {
            case 'repair':
                if ($cleanup === true) {
                    $this->removeAbstractAndUndefinedNodes($workspaceName, $dryRun);
                    $this->removeOrphanNodes($workspaceName, $dryRun);
                    $this->removeDisallowedChildNodes($workspaceName, $dryRun);
                    $this->removeUndefinedProperties($nodeType, $workspaceName, $dryRun);
                }
                $this->createMissingChildNodes($nodeType, $workspaceName, $dryRun);
                $this->reorderChildNodes($nodeType, $workspaceName, $dryRun);
                $this->addMissingDefaultValues($nodeType, $workspaceName, $dryRun);
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
     * @param NodeType $nodeType Only for this node type, if specified
     * @param string $workspaceName Name of the workspace to consider
     * @param boolean $dryRun Simulate?
     * @return void
     */
    protected function createMissingChildNodes(NodeType $nodeType = null, $workspaceName, $dryRun)
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
        $nodeCreationExceptions = 0;

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
                        $childNodeMissing = $node->getNode($childNodeName) ? false : true;
                        if ($childNodeMissing) {
                            if ($dryRun === false) {
                                $node->createNode($childNodeName, $childNodeType);
                                $this->output->outputLine('Auto created node named "%s" in "%s"', array($childNodeName, $node->getPath()));
                            } else {
                                $this->output->outputLine('Missing node named "%s" in "%s"', array($childNodeName, $node->getPath()));
                            }
                            $createdNodesCount++;
                        }
                    } catch (\Exception $exception) {
                        $this->output->outputLine('Could not create node named "%s" in "%s" (%s)', array($childNodeName, $node->getPath(), $exception->getMessage()));
                        $nodeCreationExceptions++;
                    }
                }
            }
        }

        if ($createdNodesCount !== 0 || $nodeCreationExceptions !== 0) {
            if ($dryRun === false) {
                $this->output->outputLine('Created %s new child nodes', array($createdNodesCount));

                if ($nodeCreationExceptions > 0) {
                    $this->output->outputLine('%s Errors occurred during child node creation', array($nodeCreationExceptions));
                }
            } else {
                $this->output->outputLine('%s missing child nodes need to be created', array($createdNodesCount));
            }
        }
    }

    /**
     * Performs checks for unset properties that has default values and sets them if necessary.
     *
     * @param NodeType $nodeType Only for this node type, if specified
     * @param string $workspaceName Name of the workspace to consider
     * @param boolean $dryRun Simulate?
     * @return void
     */
    public function addMissingDefaultValues(NodeType $nodeType = null, $workspaceName, $dryRun)
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
                $context = $this->nodeFactory->createContextMatchingNodeData($nodeData);
                $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
                if (!$node instanceof NodeInterface) {
                    continue;
                }
                foreach ($defaultValues as $propertyName => $defaultValue) {
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
        $this->output->outputLine('<b>Checking for nodes with abstract or undefined node types ...</b>');

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
            ->from('TYPO3\TYPO3CR\Domain\Model\NodeData', 'n')
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
                    $self->removeNodeAndChildNodesInWorkspaceByPath($node['path'], $workspaceName);
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

        /** @var \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);

        $nodes = array();
        $nodeExceptionCount = 0;
        $removeDisallowedChildNodes = function (NodeInterface $node) use (&$removeDisallowedChildNodes, &$nodes, &$nodeExceptionCount,$queryBuilder) {
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
        $this->output->outputLine('<b>Checking for orphan nodes ...</b>');

        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $workspaceList = array();
        /** @var \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        while ($workspace !== null) {
            $workspaceList[] = $workspace->getName();
            $workspace = $workspace->getBaseWorkspace();
        }

        $nodes = $queryBuilder
            ->select('n')
            ->from('TYPO3\TYPO3CR\Domain\Model\NodeData', 'n')
            ->leftJoin(
                'TYPO3\TYPO3CR\Domain\Model\NodeData',
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
     * @param NodeType $nodeType Only for this node type, if specified
     * @param string $workspaceName
     * @param boolean $dryRun Simulate?
     * @return void
     */
    public function removeUndefinedProperties(NodeType $nodeType = null, $workspaceName, $dryRun)
    {
        $this->output->outputLine('Checking for undefined properties ...');

        /** @var \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace */
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
    }

    /**
     * Creates a content context for given workspace
     *
     * @param string $workspaceName
     * @param array $dimensions
     * @return \TYPO3\TYPO3CR\Domain\Service\Context
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
            ->from('TYPO3\TYPO3CR\Domain\Model\NodeData', 'n')
            ->where('n.nodeType = :nodeType')
            ->andWhere('n.workspace = :workspace')
            ->andWhere('n.movedTo IS NULL OR n.removed = :removed')
            ->setParameter('nodeType', $nodeType)
            ->setParameter('workspace', $workspaceName)
            ->setParameter('removed', false, \PDO::PARAM_BOOL);
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Removes a node and it's children in the given workspace.
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
            ->delete('TYPO3\TYPO3CR\Domain\Model\NodeData', 'n')
            ->where('n.path LIKE :path')
            ->orWhere('n.path LIKE :subpath')
            ->andWhere('n.workspace = :workspace')
            ->setParameters(array('path' => $nodePath, 'subpath' => $nodePath . '/%', 'workspace' => $workspaceName))
            ->getQuery()
            ->execute();
    }

    /**
     * Reorder child nodes according to the current position configuration of child nodes.
     *
     * @param NodeType $nodeType Only for this node type, if specified
     * @param string $workspaceName Name of the workspace to consider
     * @param boolean $dryRun Simulate?
     * @return void
     */
    protected function reorderChildNodes(NodeType $nodeType = null, $workspaceName, $dryRun)
    {
        if ($nodeType !== null) {
            $this->output->outputLine('Checking nodes of type "%s" for child nodes that needs reordering ...', array($nodeType->getName()));
            $this->reorderChildNodesByNodeType($nodeType, $workspaceName, $dryRun);
        } else {
            $this->output->outputLine('Checking for child nodes that needs reordering ...');
            foreach ($this->nodeTypeManager->getNodeTypes() as $nodeType) {
                /** @var NodeType $nodeType */
                if ($nodeType->isAbstract()) {
                    continue;
                }
                $this->reorderChildNodesByNodeType($nodeType, $workspaceName, $dryRun);
            }
        }
    }

    /**
     * Reorder child nodes for the given node type
     *
     * @param NodeType $nodeType
     * @param string $workspaceName
     * @param boolean $dryRun
     * @return void
     */
    protected function reorderChildNodesByNodeType(NodeType $nodeType, $workspaceName, $dryRun)
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
}
