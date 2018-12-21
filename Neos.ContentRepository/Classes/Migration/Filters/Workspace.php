<?php
namespace Neos\ContentRepository\Migration\Filters;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeData;

/**
 * Filter nodes by workspace name.
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
     * Returns true if the given node is in the workspace this filter expects.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function matches(NodeData $node)
    {
        return $node->getWorkspace() !== null && $node->getWorkspace()->getName() === $this->workspaceName;
    }
}
