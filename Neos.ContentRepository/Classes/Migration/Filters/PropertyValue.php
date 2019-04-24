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
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Migration\Filters\FilterInterface;

/**
 * Filter nodes having the given property and a matching value.
 */
class PropertyValue implements FilterInterface
{
    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var string
     */
    protected $propertyValue;

    /**
     * Sets the property name to be checked.
     *
     * @param string $propertyName
     * @return void
     */
    public function setPropertyName($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Sets the property value to be checked against.
     *
     * @param string $propertyValue
     * @return void
     */
    public function setPropertyValue($propertyValue)
    {
        $this->propertyValue = $propertyValue;
    }

    /**
     * Returns true if the given node has the property and the value matches.
     *
     * @param NodeData $node
     * @return boolean
     * @throws NodeException
     */
    public function matches(NodeData $node)
    {
        return ($node->hasProperty($this->propertyName) && $node->getProperty($this->propertyName) === $this->propertyValue);
    }
}
