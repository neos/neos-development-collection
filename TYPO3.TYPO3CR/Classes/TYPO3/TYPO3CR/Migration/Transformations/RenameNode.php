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

/**
 * Rename a given node.
 */
class RenameNode extends AbstractTransformation
{
    /**
     * New name of node
     *
     * @var string
     */
    protected $newName;

    /**
     * Sets the new name for the node to change.
     *
     * @param string $newName
     * @return void
     */
    public function setNewName($newName)
    {
        $this->newName = $newName;
    }

    /**
     * Returns TRUE if the given node does not yet have the new name.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return ($node->getName() !== $this->newName);
    }

    /**
     * Renames the node to the new name.
     *
     * @param NodeData $node
     * @return void
     */
    public function execute(NodeData $node)
    {
        $newNodePath = $node->getParentPath() . '/' . $this->newName;
        $node->setPath($newNodePath);
    }
}
