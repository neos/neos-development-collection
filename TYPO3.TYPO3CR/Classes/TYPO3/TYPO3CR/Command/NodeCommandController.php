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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Node command controller for the TYPO3.TYPO3CR package
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

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
	 * Repair inconsistent nodes
	 *
	 * This command analyzes and repairs the node tree structure and individual nodes
	 * based on the current node type configuration.
	 *
	 * The following checks will be performed:
	 *
	 * 1. Missing child nodes
	 *
	 * For all nodes (or only those which match the --node-type filter specified with this
	 * command) which currently don't have child nodes as configured by the node type's
	 * configuration new child nodes will be created.
	 *
	 * Examples:
	 *
	 * ./flow node:repair
	 *
	 * ./flow node:repair --node-type TYPO3.Neos.NodeTypes:Page
	 *
	 * @param string $nodeType Node type name, if empty update all declared node types
	 * @param string $workspace Workspace name, default is 'live'
	 * @param boolean $dryRun Don't do anything, but report actions
	 * @return void
	 */
	public function repairCommand($nodeType = NULL, $workspace = 'live', $dryRun = FALSE) {
		if ($this->workspaceRepository->findByName($workspace)->count() === 0) {
			$this->outputLine('Workspace "%s" does not exist', array($workspace));
			exit(1);
		}

		if ($nodeType !== NULL) {
			if ($this->nodeTypeManager->hasNodeType($nodeType)) {
				$nodeType = $this->nodeTypeManager->getNodeType($nodeType);
			} else {
				$this->outputLine('Node type "%s" does not exist', array($nodeType));
				exit(1);
			}
		}

		if ($dryRun) {
			$this->outputLine('Dry run, not committing any changes.');
		}

		$this->createMissingChildNodes($nodeType, $workspace, $dryRun);
		$this->outputLine('Node repair finished.');
	}

	/**
	 * Create missing child nodes
	 *
	 * This is a legacy command which automatically creates missing child nodes for a
	 * node type based on the structure defined in the NodeTypes configuration.
	 *
	 * NOTE: Usage of this command is deprecated and it will be remove eventually.
	 *       Please use node:repair instead.
	 *
	 * @param string $nodeType Node type name, if empty update all declared node types
	 * @param string $workspace Workspace name, default is 'live'
	 * @param boolean $dryRun Don't do anything, but report missing child nodes
	 * @return void
	 * @see typo3.typo3cr:node:repair
	 * @deprecated since 1.2
	 */
	public function autoCreateChildNodesCommand($nodeType = NULL, $workspace = 'live', $dryRun = FALSE) {
		$this->createMissingChildNodes($nodeType, $workspace, $dryRun);
	}

	/**
	 * Performs checks for missing child nodes according to the node's auto-create configuration and creates
	 * them if necessary.
	 *
	 * @param NodeType $nodeType Only for this node type, if specified
	 * @param string $workspace Name of the workspace to consider
	 * @param boolean $dryRun Simulate?
	 * @return void
	 */
	protected function createMissingChildNodes(NodeType $nodeType = NULL, $workspace, $dryRun) {
		if ($nodeType !== NULL) {
			$this->outputLine('Checking nodes of type "%s" for missing child nodes ...', array($nodeType->getName()));
			$this->createChildNodesByNodeType($nodeType, $workspace, $dryRun);
		} else {
			$this->outputLine('Checking for missing child nodes ...');
			foreach ($this->nodeTypeManager->getNodeTypes() as $nodeType) {
				/** @var NodeType $nodeType */
				if ($nodeType->isAbstract()) {
					continue;
				}
				$this->createChildNodesByNodeType($nodeType, $workspace, $dryRun);
			}
		}
	}

	/**
	 * Create missing child nodes for the given node type
	 *
	 * @param NodeType $nodeType
	 * @param string $workspace
	 * @param boolean $dryRun
	 * @return void
	 */
	protected function createChildNodesByNodeType(NodeType $nodeType, $workspace, $dryRun) {
		$createdNodesCount = 0;
		$nodeCreationExceptions = 0;

		$nodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), FALSE);
		$nodeTypes[$nodeType->getName()] = $nodeType;

		if ($this->nodeTypeManager->hasNodeType((string)$nodeType)) {
			$nodeType = $this->nodeTypeManager->getNodeType((string)$nodeType);
			$nodeTypeNames[$nodeType->getName()] = $nodeType;
		} else {
			$this->outputLine('Node type "%s" does not exist', array((string)$nodeType));
			exit(1);
		}

		/** @var $nodeType NodeType */
		foreach ($nodeTypes as $nodeTypeName => $nodeType) {
			$childNodes = $nodeType->getAutoCreatedChildNodes();
			$context = $this->createContext($workspace);
			foreach ($this->nodeDataRepository->findByNodeType($nodeTypeName) as $nodeData) {
				$node = $this->nodeFactory->createFromNodeData($nodeData, $context);
				if (!$node instanceof NodeInterface || $node->isRemoved() === TRUE) {
					continue;
				}
				foreach ($childNodes as $childNodeName => $childNodeType) {
					try {
						$childNodeMissing = $node->getNode($childNodeName) ? FALSE : TRUE;
						if ($childNodeMissing) {
							if ($dryRun === FALSE) {
								$node->createNode($childNodeName, $childNodeType);
								$this->outputLine('Auto created node named "%s" in "%s"', array($childNodeName, $node->getPath()));
							} else {
								$this->outputLine('Missing node named "%s" in "%s"', array($childNodeName, $node->getPath()));
							}
							$createdNodesCount++;
						}
					} catch (\Exception $exception) {
						$this->outputLine('Could not create node named "%s" in "%s" (%s)', array($childNodeName, $node->getPath(), $exception->getMessage()));
						$nodeCreationExceptions++;
					}
				}
			}
		}

		if ($createdNodesCount !== 0 || $nodeCreationExceptions !== 0) {
			if ($dryRun === FALSE) {
				$this->outputLine('Created %s new child nodes', array($createdNodesCount));

				if ($nodeCreationExceptions > 0) {
					$this->outputLine('%s Errors occurred during child node creation', array($nodeCreationExceptions));
				}
			} else {
				$this->outputLine('%s missing child nodes need to be created', array($createdNodesCount));
			}
		}
	}

	/**
	 * Creates a content context for given workspace
	 *
	 * @param string $workspaceName
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function createContext($workspaceName) {
		return $this->contextFactory->create(array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		));
	}

}
