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
 * Filter nodes having the given property and its value not empty.
 */
class PropertyNotEmpty implements FilterInterface
{
    /**
     * The name of the property to be checked for non-empty value.
     *
     * @var string
     */
    protected $propertyName;

    /**
     * Sets the property name to be checked for non-empty value.
     *
     * @param string $propertyName
     * @return void
     */
    public function setPropertyName($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Returns true if the given node has the property and the value is not empty.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function matches(NodeData $node)
    {
        if ($node->hasProperty($this->propertyName)) {
            $propertyValue = $node->getProperty($this->propertyName);
            return !empty($propertyValue);
        }
        return false;
    }
}
