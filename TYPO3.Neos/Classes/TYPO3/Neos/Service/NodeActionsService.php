<?php
namespace TYPO3\Neos\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
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
     * Executes the actions configured in $actionData on the given $node.
     *
     * @param NodeInterface $node
     * @param array $actionData
     * @throws \TYPO3\Neos\Exception
     * @return void
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
     * Returns action configuration if anything is configured, otherwise null is returned.
     *
     * @param NodeInterface $node
     * @return array
     */
    protected function getActionConfigurations(NodeInterface $node)
    {
        if ($node->getNodeType()->hasConfiguration('options.actions.onCreate')) {
            return $node->getNodeType()->getConfiguration('options.actions.onCreate');
        }

        return null;
    }
}
