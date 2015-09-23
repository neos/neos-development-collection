<?php
namespace TYPO3\TYPO3CR\Migration\Filters;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Filter nodes by node name.
 */
class Workspace implements FilterInterface
{
    /**
     * The workspace name to match on.
     *
     * @var string
     */
    protected $workspaceName;

    /**
     * Sets the workspace name to match on.
     *
     * @param string $nodeName
     * @return void
     */
    public function setWorkspaceName($nodeName)
    {
        $this->workspaceName = $nodeName;
    }

    /**
     * Returns TRUE if the given node is in the workspace this filter expects.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return boolean
     */
    public function matches(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        return $node->getWorkspace() !== null && $node->getWorkspace()->getName() === $this->workspaceName;
    }
}
