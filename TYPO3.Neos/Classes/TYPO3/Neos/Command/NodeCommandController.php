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
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

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
	 * Create missing childNodes for a node type
	 *
	 * This command automatically creates missing child nodes for a node type
	 * based on the structure defined in the NodeTypes configuration.
	 *
	 * Example for creating child nodes for the TYPO3.Neos.NodeTypes:Page node type in the
	 * live workspace:
	 *
	 * ./flow node:autocreatechildnodes --node-type TYPO3.Neos.NodeTypes:Page
	 *
	 * @param string $nodeType Node type name
	 * @param string $workspace Workspace name, default is 'live'
	 * @return void
	 */
	public function autoCreateChildNodesCommand($nodeType, $workspace = 'live') {
		$nodeTypeName = $nodeType;
		if ($this->workspaceRepository->findByName($workspace)->count() === 0) {
			$this->outputLine('Workspace "%s" does not exist', array($workspace));
			$this->quit(1);
		}
		if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
			$this->outputLine('Node type "%s" does not exist', array($nodeTypeName));
			$this->quit(1);
		}

		$createdNodesCount = 0;
		$nodeCreationExceptions = 0;

		$nodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeTypeName, FALSE);
		$nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
		$nodeTypes[$nodeType->getName()] = $nodeType;

		/** @var NodeType $nodeType */
		foreach ($nodeTypes as $nodeTypeName => $nodeType) {
			$childNodes = $nodeType->getAutoCreatedChildNodes();
			$context = $this->createContext($workspace);
			foreach ($this->nodeDataRepository->findByNodeType($nodeTypeName) as $nodeData) {
				$node = $this->nodeFactory->createFromNodeData($nodeData, $context);
				foreach ($childNodes as $childNodeName => $childNodeType) {
					try {
						$node->createNode($childNodeName, $childNodeType);
						$this->outputLine('Auto created node named "%s" in "%s"', array($childNodeName, $node->getPath()));
						$createdNodesCount++;
					} catch (\TYPO3\TYPO3CR\Exception\NodeExistsException $exception) {
						// Silently ignore this exception as we expect this to happen if the node already exists
					} catch (\Exception $exception) {
						$this->outputLine('Could not create node named "%s" in "%s" (%s)', array($childNodeName, $node->getPath(), $exception->getMessage()));
						$nodeCreationExceptions++;
					}
				}
			}
		};

		if ($createdNodesCount === 0 && $nodeCreationExceptions === 0) {
			$this->outputLine('Node structure for workspace "%s" already up to date', array($workspace));
		} else {
			$this->outputLine('Created %s new child nodes', array($createdNodesCount));

			if ($nodeCreationExceptions > 0) {
				$this->outputLine('%s Errors occurred during child node creation', array($nodeCreationExceptions));
			}
		}
	}

	/**
	 * Creates a content context for given workspace
	 *
	 * @param string $workspaceName
	 * @return \TYPO3\TYPO3CR\Domain\Service\ContextInterface
	 */
	protected function createContext($workspaceName) {
		return $this->contextFactory->create(array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		));
	}

}
