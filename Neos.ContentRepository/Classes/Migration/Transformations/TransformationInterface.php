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
 * Transformation are used to change nodes as needed. They may do anything to a node
 * in the execute() method and will be asked if they can transform a node through a
 * call to isTransformable().
 *
 * Settings given to a transformation will be passed to accordingly named setters.
 */
interface TransformationInterface
{
    /**
     * Returns true if the given node can be transformed by this transformation.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node);

    /**
     * Execute the transformation on the given node.
     *
     * @param NodeData $node
     * @return NodeData
     */
    public function execute(NodeData $node);
}
