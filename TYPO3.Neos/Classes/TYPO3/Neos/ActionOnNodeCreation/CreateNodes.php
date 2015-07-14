<?php
namespace TYPO3\Neos\ActionOnNodeCreation;

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
use TYPO3\Neos\Service\NodeOperations;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Base class for Actions On Node Creation. Individual actions should extend this class.
 */
class CreateNodes extends AbstractActionOnNodeCreation
{
    /**
     * @Flow\Inject
     * @var NodeOperations
     */
    protected $nodeOperations;

    /**
     * Execute the action (e.g. change properties or create child nodes)
     *
     * @param NodeInterface $node
     * @param array $options
     * @return void
     */
    public function execute(NodeInterface $node, array $options)
    {
        if (!isset($options['nodeAmount'])) {
            $options['nodeAmount'] = 1;
        }

        if (isset($options['nodePath']) && !empty($options['nodePath'])) {
            $referenceNode = $node->getNode($options['nodePath']);
        } else {
            $referenceNode = $node;
        }

        for ($i=0; $i<$options['nodeAmount']; $i++) {
            $this->nodeOperations->create($referenceNode, $options['nodeData'], 'into');
        }
    }
}
