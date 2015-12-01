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
use TYPO3\Eel\Utility as EelUtility;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Functions for executing actions on node creation (e.g. creating child nodes or setting properties based on wizard
 * data)
 *
 * @Flow\Scope("singleton")
 */
class NodeActionsService
{
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
     * @Flow\Inject(lazy=FALSE)
     * @var \TYPO3\Eel\CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Migration\Service\NodeTransformation
     */
    protected $nodeTransformationService;

    /**
     * Called by the Flow object framework after creating the object and resolving all dependencies.
     *
     * @param integer $cause Creation cause
     */
    public function initializeObject($cause)
    {
        if ($cause === \TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
            $this->settings = $this->configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Neos.nodeActions');
        }
    }

    /**
     * @param NodeInterface $node
     * @param array $actionData
     * @throws \TYPO3\Neos\Exception
     */
    public function processActions(NodeInterface $node, array $actionData)
    {
        $actionConfigurations = $this->getActionConfigurations($node);
        $contextVariables = $this->getContextVariables($node, $actionData);
        foreach ($actionConfigurations as &$actionConfiguration) {
            if (isset($actionConfiguration['settings'])) {
                $actionConfiguration['settings'] = $this->parseSettings($actionConfiguration['settings'], $contextVariables);
            }
        }
        $this->nodeTransformationService->execute($node->getNodeData(), $actionConfigurations);
    }

    /**
     * @param NodeInterface $node
     * @param array $actionData
     * @return array
     */
    protected function getContextVariables(NodeInterface $node, array $actionData)
    {
        $contextVariables = EelUtility::getDefaultContextVariables($this->settings['defaultContext']);
        $contextVariables['data'] = $actionData;
        $contextVariables['node'] = $node;

        $flowQuery = new FlowQuery(array($node));
        $contextVariables['documentNode'] = $flowQuery->closest('[instanceof TYPO3.Neos:Document]')->get(0);

        return $contextVariables;
    }

    /**
     * @param array $settings
     * @param array $contextVariables
     * @return array
     */
    protected function parseSettings(array $settings, array $contextVariables)
    {
        foreach ($settings as &$setting) {
            if (is_array($setting)) {
                $setting = $this->parseSettings($setting, $contextVariables);
            } elseif (is_string($setting)) {
                if (preg_match(\TYPO3\Eel\Package::EelExpressionRecognizer, $setting)) {
                    $setting = EelUtility::evaluateEelExpression($setting, $this->eelEvaluator, $contextVariables);
                }
            }
        }
        return $settings;
    }

    /**
     * @param NodeInterface $node
     * @return array
     */
    protected function getActionConfigurations(NodeInterface $node)
    {
        $actionConfigurations = [];
        if ($node->getNodeType()->hasConfiguration('options.actions.onCreate')) {
            $actionConfigurations = $node->getNodeType()->getConfiguration('options.actions.onCreate');
        }
        return $actionConfigurations;
    }
}
