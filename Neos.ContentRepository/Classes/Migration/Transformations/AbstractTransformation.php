<?php
namespace Neos\ContentRepository\Migration\Transformations;

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
 * Abstract transformation class, transformations should inherit from this.
 */
abstract class AbstractTransformation implements TransformationInterface
{
    /**
     * Returns true, indicating that the given node can be transformed by this transformation.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return true;
    }

    /**
     * Execute the transformation on the given node.
     *
     * This implementation returns the given node unchanged.
     *
     * @param NodeData $node
     * @return NodeData
     */
    public function execute(NodeData $node)
    {
        return $node;
    }
}
