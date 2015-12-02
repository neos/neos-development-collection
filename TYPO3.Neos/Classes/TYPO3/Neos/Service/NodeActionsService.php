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
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Migration\Service\NodeTransformation
     */
    protected $nodeTransformationService;

    /**
     * @param NodeInterface $node
     * @param array $actionData
     * @throws \TYPO3\Neos\Exception
     */
    public function processActions(NodeInterface $node, array $actionData)
    {
        $actionConfigurations = $this->getActionConfigurations($node);
        if ($actionConfigurations !== null) {
            $contextVariables['data'] = $actionData;
            $this->nodeTransformationService->execute($node->getNodeData(), $actionConfigurations, $contextVariables);
        }
    }

    /**
     * @param NodeInterface $node
     * @return array
     */
    protected function getActionConfigurations(NodeInterface $node)
    {
        $actionConfigurations = null;
        if ($node->getNodeType()->hasConfiguration('options.actions.onCreate')) {
            $actionConfigurations = $node->getNodeType()->getConfiguration('options.actions.onCreate');
        }
        return $actionConfigurations;
    }
}
