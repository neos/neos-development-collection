<?php
namespace TYPO3\Neos\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Node command controller for the TYPO3.Neos package
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * Create missing child nodes
	 *
	 * This command automatically creates missing child nodes for a node type
	 * based on the structure defined in the NodeTypes configuration.
	 *
	 * Example for creating child nodes for the TYPO3.Neos.NodeTypes:Page node type in the
	 * live workspace:
	 *
	 * ./flow node:autocreatechildnodes --node-type TYPO3.Neos.NodeTypes:Page
	 *
	 * @param string $nodeType Node type name, if empty update all declared node types
	 * @param string $workspace Workspace name, default is 'live'
	 * @param boolean $dryRun Don't do anything, but report missing child nodes
	 * @return void
	 */
	public function autoCreateChildNodesCommand($nodeType = NULL, $workspace = 'live', $dryRun = FALSE) {
		if ($this->workspaceRepository->findByName($workspace)->count() === 0) {
			$this->outputLine('Workspace "%s" does not exist', array($workspace));
			$this->quit(1);
		}

		if ($nodeType !== NULL) {
			if ($this->nodeTypeManager->hasNodeType($nodeType)) {
				$nodeType = $this->nodeTypeManager->getNodeType($nodeType);
			} else {
				$this->outputLine('Node type "%s" does not exist', array($nodeType));
				$this->quit(1);
			}
			$this->createChildNodesByNodeType($nodeType, $workspace, $dryRun);
		} else {
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
			$this->quit(1);
		}

		$this->outputLine();
		$this->outputLine('Working on node type "%s" ...', array((string)$nodeType));

		/** @var $nodeType NodeType */
		foreach ($nodeTypes as $nodeTypeName => $nodeType) {
			$childNodes = $nodeType->getAutoCreatedChildNodes();
			$context = $this->createContext($workspace);
			foreach ($this->nodeDataRepository->findByNodeType($nodeTypeName) as $nodeData) {
				/* @var $nodeData NodeData */
				if ($nodeData->getWorkspace()->getName() !== $workspace) {
					// We'll only work on nodes which exist materialized in the specified workspace; else we continue
					// with the next node.
					continue;
				}
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
		};

		if ($createdNodesCount === 0 && $nodeCreationExceptions === 0) {
			$this->outputLine('All "%s" nodes in workspace "%s" have an up-to-date structure', array((string)$nodeType, $workspace));
		} else {
			if ($dryRun === FALSE) {
				$this->outputLine('Created %s new child nodes', array($createdNodesCount));

				if ($nodeCreationExceptions > 0) {
					$this->outputLine('%s Errors occurred during child node creation', array($nodeCreationExceptions));
				}
			} else {
				$this->outputLine('%s missing child nodes need to be created', array($createdNodesCount));
			}
		}
		$this->outputLine();
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
