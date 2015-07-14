<?php
namespace TYPO3\Neos\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Neos\ActionOnNodeCreation\ActionOnNodeCreationInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\Eel\Utility as EelUtility;

/**
 * Functions for executing actions on node creation (e.g. creating child nodes or setting properties based on wizard
 * data)
 *
 * @Flow\Scope("singleton")
 */
class ActionsOnNodeCreationService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @Flow\Inject
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject(lazy=FALSE)
	 * @var \TYPO3\Eel\CompilingEvaluator
	 */
	protected $eelEvaluator;

	/**
	 * Called by the Flow object framework after creating the object and resolving all dependencies.
	 *
	 * @param integer $cause Creation cause
	 */
	public function initializeObject($cause) {
		if ($cause === \TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
			$this->settings = $this->configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Neos.actionsOnNodeCreation');
		}
	}

	/**
	 * @param NodeInterface $node
	 * @param array $actionData
	 * @throws \TYPO3\Neos\Exception
	 */
	public function processActions(NodeInterface $node, array $actionData) {
		$actionConfigurations = $this->getActionConfigurations($node);

		foreach ($actionConfigurations as $actionConfiguration) {
			$actionClassName = $this->resolveActionClassName($actionConfiguration['type']);
			$action = new $actionClassName();
			if ($action instanceof ActionOnNodeCreationInterface) {
				if ($action->isActionable($node, $actionData)) {
					if (!isset($contextVariables)) {
						$contextVariables = $this->getContextVariables($node, $actionData);
					}
					if (isset($actionConfiguration['options'])) {
						$options = $this->parseOptions($actionConfiguration['options'], $contextVariables);
					} else {
						$options = [];
					}
					$action->execute($node, $options);
				}
			} else {
				throw new \TYPO3\Neos\Exception('The action of type "' . $actionConfigurations['type'] . '" does not implement ActionOnNodeCreationInterface.', 1436875075);
			}
		}
	}

	/**
	 * @param NodeInterface $node
	 * @param array $actionData
	 * @return array
	 */
	protected function getContextVariables(NodeInterface $node, array $actionData) {
		$contextVariables = EelUtility::getDefaultContextVariables($this->settings['defaultContext']);
		$contextVariables['data'] = $actionData;
		$contextVariables['node'] = $node;

		$flowQuery = new FlowQuery(array($node));
		$contextVariables['documentNode'] = $flowQuery->closest('[instanceof TYPO3.Neos:Document]')->get(0);

		return $contextVariables;
	}

	/**
	 * @param array $options
	 * @param array $contextVariables
	 * @return array
	 */
	protected function parseOptions(array $options, array $contextVariables) {
		foreach ($options as &$option) {
			if (is_array($option)) {
				$option = $this->parseOptions($option, $contextVariables);
			} elseif (is_string($option)) {
				if (preg_match(\TYPO3\Eel\Package::EelExpressionRecognizer, $option)) {
					$option = EelUtility::evaluateEelExpression($option, $this->eelEvaluator, $contextVariables);
				}
			}
		}
		return $options;
	}

	/**
	 * @param NodeInterface $node
	 * @return array
	 */
	protected function getActionConfigurations(NodeInterface $node) {
		$actionConfigurations = [];
		if ($node->getNodeType()->hasConfiguration('options.actionsOnNodeCreation')) {
			$actionConfigurations = $node->getNodeType()->getConfiguration('options.actionsOnNodeCreation');
		}
		return $actionConfigurations;
	}

	/**
	 * Tries to resolve the given action name into a class name.
	 *
	 * The name can be a fully qualified class name or a name relative to the
	 * TYPO3\Neos\ActionOnNodeCreation namespace.
	 *
	 * @param string $actionName
	 * @return string
	 * @throws \TYPO3\Neos\Exception
	 */
	protected function resolveActionClassName($actionName) {
		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($actionName);
		if ($resolvedObjectName !== FALSE) {
			return $resolvedObjectName;
		}

		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('TYPO3\Neos\ActionOnNodeCreation\\' . $actionName);
		if ($resolvedObjectName !== FALSE) {
			return $resolvedObjectName;
		}

		throw new \TYPO3\Neos\Exception('A action with the name "' . $actionName . '" could not be found.', 1436875061);
	}



}