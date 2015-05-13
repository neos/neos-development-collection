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
use TYPO3\Flow\Cli\ConsoleOutput;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\TYPO3CR\Command\NodeCommandControllerPluginInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Utility;

/**
 * A plugin for the TYPO3CR NodeCommandController which adds a task adding missing URI segments to the node:repair
 * command.
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
	 * @var WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var ContentDimensionRepository
	 */
	protected $contentDimensionRepository;

	/**
	 * @var ContentDimensionPresetSourceInterface
	 * @Flow\Inject
	 */
	protected $contentDimensionPresetSource;

	/**
	 * @var ConsoleOutput
	 */
	protected $output;

	/**
	 * Returns a short description
	 *
	 * @param string $controllerCommandName Name of the command in question, for example "repair"
	 * @return string A piece of text to be included in the overall description of the node:xy command
	 */
	static public function getSubCommandShortDescription($controllerCommandName) {
		switch ($controllerCommandName) {
			case 'repair':
				return 'Generate missing URI path segments';
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
					'<u>Generate missing URI path segments</u>' . PHP_EOL .
					PHP_EOL .
					'Generates URI path segment properties for all document nodes which don\'t have a path' . PHP_EOL .
					'segment set yet.' . PHP_EOL;
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
	public function invokeSubCommand($controllerCommandName, ConsoleOutput $output, NodeType $nodeType = NULL, $workspaceName = 'live', $dryRun = FALSE, $cleanup = TRUE) {
		$this->output = $output;
		switch ($controllerCommandName) {
			case 'repair':
				$this->generateUriPathSegments($workspaceName, $dryRun);
		}
	}

	/**
	 * Generate missing URI path segments
	 *
	 * This generates URI path segment properties for all document nodes which don't have
	 * a path segment set yet.
	 *
	 * @param string $workspaceName
	 * @param boolean $dryRun
	 * @return void
	 */
	public function generateUriPathSegments($workspaceName, $dryRun) {
		$contentDimensionPresets = $this->contentDimensionPresetSource->getAllPresets();
		if (isset($contentDimensionPresets['language']['presets'])) {
			foreach ($contentDimensionPresets['language']['presets'] as $languagePreset) {
				$this->output->outputLine('Migrating nodes for %s', array($languagePreset['label']));
				$context = $this->createContext($workspaceName, $languagePreset['values']);
				foreach ($context->getRootNode()->getChildNodes() as $siteNode) {
					$this->generateUriPathSegmentsForSubtree($siteNode, $dryRun);
				}
			}
		} else {
			$context = $this->createContext($workspaceName);
			foreach ($context->getRootNode()->getChildNodes() as $siteNode) {
				$this->generateUriPathSegmentsForSubtree($siteNode, $dryRun);
			}
		}
	}

	/**
	 * Traverses through the tree starting at the given root node and sets the uriPathSegment property derived from
	 * the node label. If $force is set, uriPathSegment is overwritten even if it already contained a value.
	 *
	 * @param NodeInterface $rootNode The node where the traversal starts
	 * @param boolean $dryRun
	 * @return void
	 */
	protected function generateUriPathSegmentsForSubtree(NodeInterface $rootNode, $dryRun) {
		foreach ($rootNode->getChildNodes('TYPO3.Neos:Document') as $node) {
			/** @var NodeInterface $node */
			if ($node->getProperty('uriPathSegment') == '') {
				$uriPathSegment = Utility::renderValidNodeName($node->getName());
				if ($dryRun === FALSE) {
					$node->setProperty('uriPathSegment', $uriPathSegment);
				}
				$this->output->outputLine('%s (%s) => %s', array($node->getPath(), $node->getName(), $uriPathSegment));
			}
			if ($node->hasChildNodes('TYPO3.Neos:Document')) {
				$this->generateUriPathSegmentsForSubtree($node, $dryRun);
			}
		}
	}

	/**
	 * Creates a content context for given workspace and language identifiers
	 *
	 * @param string $workspaceName
	 * @param array $languageIdentifiers
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function createContext($workspaceName, array $languageIdentifiers = NULL) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		);
		if ($languageIdentifiers !== NULL) {
			$contextProperties = array_merge($contextProperties, array(
				'dimensions' => array('language' => $languageIdentifiers)
			));
		}
		return $this->contextFactory->create($contextProperties);
	}

}