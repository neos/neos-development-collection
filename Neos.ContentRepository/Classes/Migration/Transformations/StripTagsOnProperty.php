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
 * Strip all tags on a given property
 */
class StripTagsOnProperty extends AbstractTransformation
{
    /**
     * Property name to change
     *
     * @var string
     */
    protected $propertyName;

    /**
     * Sets the name of the property to work on.
     *
     * @param string $propertyName
     * @return void
     */
    public function setProperty($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Returns true if the given node has the property to work on.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return ($node->hasProperty($this->propertyName));
    }

    /**
     * Strips tags on the value of the property to work on.
     *
     * @param NodeData $node
     * @return void
     */
    public function execute(NodeData $node)
    {
        $node->setProperty($this->propertyName, strip_tags($node->getProperty($this->propertyName)));
    }
}
