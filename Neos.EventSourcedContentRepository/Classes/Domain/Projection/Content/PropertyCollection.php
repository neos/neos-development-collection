<?php

namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\PropertyCollectionInterface;
use Neos\EventSourcedContentRepository\Domain;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Annotations as Flow;


final class PropertyCollection implements PropertyCollectionInterface
{

    /**
     * Properties from Nodes
     *
     * @var array
     */
    protected $properties;

    /**
     * @var array
     */
    protected $resolvedPropertyObjects;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->properties[$offset])) {
            return null;
        }
        $value = $this->properties[$offset];
        if (isset($value['__flow_object_type'])) {
            if (!isset($this->resolvedPropertyObjects[$offset])) {
                $this->resolvedPropertyObjects[$offset] = $this->persistenceManager->getObjectByIdentifier($value['__identifier'], $value['__flow_object_type']);
            }
            return $this->resolvedPropertyObjects[$offset];
        }

        return $value;
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException("Do not use!");
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException("Do not use!");
    }
}
