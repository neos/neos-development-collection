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

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeData;

/**
 * Filter nodes by node type.
 */
class NodePath implements FilterInterface
{

    /**
     * The node name to match on.
     *
     * @var string
     */
    protected $nodePath;

    /**
     * Sets the node path starting with /sites/{nodePath}
     *
     * @param $nodePath
     * @return void
     */
    public function setPath($nodePath)
    {
        $this->nodePath = $nodePath;
    }

    /**
     * Returns TRUE if the given node path is starting with the corresponding filter path.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function matches(NodeData $node)
    {
        $nodePathMatch = strpos($node->getPath(), $this->nodePath);

        return $nodePathMatch !== false;
    }
}
