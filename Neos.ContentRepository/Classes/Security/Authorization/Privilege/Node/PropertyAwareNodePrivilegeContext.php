<?php
namespace Neos\ContentRepository\Security\Authorization\Privilege\Node;

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

/**
 * An Eel context matching expression for the node privileges including
 * node properties.
 */
class PropertyAwareNodePrivilegeContext extends NodePrivilegeContext
{
    /**
     * @var array
     */
    protected $propertyNames = array();

    /**
     * @param string|array $propertyNames
     * @return boolean
     */
    public function nodePropertyIsIn($propertyNames)
    {
        if (!is_array($propertyNames)) {
            $propertyNames = array($propertyNames);
        }
        $this->propertyNames = $propertyNames;
        return true;
    }

    /**
     * @return array
     */
    public function getNodePropertyNames()
    {
        return $this->propertyNames;
    }

    /**
     * Whether or not this context is bound to specific properties
     *
     * @return boolean
     */
    public function hasProperties()
    {
        return $this->propertyNames !== array();
    }
}
