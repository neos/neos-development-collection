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

/**
 * An Eel context matching expression for the node privileges including
 * node properties.
 */
class PropertyAwareNodePrivilegeContext extends NodePrivilegeContext
{
    /**
     * @var array<int,string>
     */
    protected array $propertyNames = [];

    /**
     * @param string|array<int,string> $propertyNames
     * @return boolean
     */
    public function nodePropertyIsIn(string|array $propertyNames): bool
    {
        if (!is_array($propertyNames)) {
            $propertyNames = [$propertyNames];
        }
        $this->propertyNames = $propertyNames;
        return true;
    }

    /**
     * @return array<int,string>
     */
    public function getNodePropertyNames(): array
    {
        return $this->propertyNames;
    }

    /**
     * Whether or not this context is bound to specific properties
     */
    public function hasProperties(): bool
    {
        return $this->propertyNames !== [];
    }
}
