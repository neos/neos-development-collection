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

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Change the node type.
 */
class ChangeNodeType extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * The new Node Type to use as a string
     *
     * @var string
     */
    protected $newType;

    /**
     * @param string $newType
     * @return void
     */
    public function setNewType($newType)
    {
        $this->newType = $newType;
    }

    /**
     * If the given node has the property this transformation should work on, this
     * returns true if the given NodeType is registered with the NodeTypeManager and is not abstract.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return $this->nodeTypeManager->hasNodeType($this->newType) && !$this->nodeTypeManager->getNodeType($this->newType)->isAbstract();
    }

    /**
     * Change the Node Type on the given node.
     *
     * @param NodeData $node
     * @return void
     */
    public function execute(NodeData $node)
    {
        $nodeType = $this->nodeTypeManager->getNodeType($this->newType);
        $node->setNodeType($nodeType);
    }
}
