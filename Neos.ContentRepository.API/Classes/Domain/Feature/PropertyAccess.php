<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Api\Domain\Feature;

/*
 * This file is part of the Neos.ContentRepository.Api package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\PropertyCollectionInterface;

/**
 * The feature trait implementing the property access interface based on a node
 */
trait PropertyAccess
{
    private NodeInterface $node;

    /**
     * Returns all properties of this node. References are NOT part of this API; there you need to check getReference() and getReferences()
     */
    public function getProperties(): PropertyCollectionInterface
    {
        return $this->node->getProperties();
    }

    /**
     * Returns the specified property.
     *
     * If the node has a content object attached, the property will be fetched
     * there if it is gettable.
     */
    public function getProperty(string $propertyName)
    {
        return $this->node->getProperty($propertyName);
    }

    /**
     * If this node has a property with the given name. Does NOT check the NodeType; but checks
     * for a non-NULL property value.
     */
    public function hasProperty(string $propertyName): bool
    {
        return $this->node->hasProperty($propertyName);
    }
}
