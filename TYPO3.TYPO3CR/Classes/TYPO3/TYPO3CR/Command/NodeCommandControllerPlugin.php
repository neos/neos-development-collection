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
use TYPO3\Flow\Cli\ConsoleOutput;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Plugin for the TYPO3CR NodeCommandController which provides functionality for creating missing child nodes.
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandControllerPlugin implements NodeCommandControllerPluginInterface {

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
	 * @var ConsoleOutput
	 */
	protected $output;

	/**
	 * @var array
	 */
	protected $pluginConfigurations = array();

	/**
	 * Returns a short description
	 *
	 * @param string $controllerCommandName Name of the command in question, for example "repair"
	 * @return string A piece of text to be included in the overall description of the node:xy command
	 */
	static public function getSubCommandShortDescription($controllerCommandName) {
		switch ($controllerCommandName) {
			case 'repair':
				return 'Check and fix possibly missing child nodes';
			break;
		}
	}

	/**
	 * Returns a piece of description for the specific task the plugin solves for the specified command
	 *
	 * @param string $controllerCommandName Name of the command in question, for example "repair"
	 * @return string A piece of text to be included in the overall description of the node:xy command
	 */
	static public function getSubCommandDescription($controllerCommandName) {
		switch ($controllerCommandName) {
			case 'repair':
				return
					'<u>Missing child nodes</u>' . PHP_EOL .
					PHP_EOL .
					'For all nodes (or only those which match the --node-type filter specified with this' . PHP_EOL .
					'command) which currently don\'t have child nodes as configured by the node type\'s' . PHP_EOL .
					'configuration new child nodes will be created.' . PHP_EOL;
			break;
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
	 * @return void
	 */
	public function invokeSubCommand($controllerCommandName, ConsoleOutput $output, NodeType $nodeType = NULL, $workspaceName = 'live', $dryRun = FALSE) {
		$this->output = $output;
		$this->createMissingChildNodes($nodeType, $workspaceName, $dryRun);
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
	protected function createMissingChildNodes(NodeType $nodeType = NULL, $workspaceName, $dryRun) {
		if ($nodeType !== NULL) {
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
			$this->output->outputLine('Node type "%s" does not exist', array((string)$nodeType));
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
			if ($dryRun === FALSE) {
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