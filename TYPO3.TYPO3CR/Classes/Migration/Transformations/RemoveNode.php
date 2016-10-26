<?php
namespace TYPO3\TYPO3CR\Migration\Transformations;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * Remove a given node (hard).
 */
class RemoveNode extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * Remove the given node
     *
     * @param NodeData $node
     * @return void
     */
    public function execute(NodeData $node)
    {
        $node->setRemoved(true);
    }
}
