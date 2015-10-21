<?php
namespace TYPO3\TYPO3CR\Migration\Filters;

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

/**
 * Filter nodes by node name.
 */
class NodeName implements FilterInterface
{
    /**
     * The node name to match on.
     *
     * @var string
     */
    protected $nodeName;

    /**
     * Sets the node type name to match on.
     *
     * @param string $nodeName
     * @return void
     */
    public function setNodeName($nodeName)
    {
        $this->nodeName = $nodeName;
    }

    /**
     * Returns TRUE if the given node is of the node type this filter expects.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return boolean
     */
    public function matches(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        return $node->getName() === $this->nodeName;
    }
}
