<?php

namespace Neos\ContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\PropertyName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;

/**
 * The property collection implementation --> TODO: Move to TraversableNode!!
 *
 * Takes care of lazily resolving entity properties
 */
class PropertyCollection implements \ArrayAccess, \Iterator
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $properties;

    /**
     * @var array
     */
    protected $resolvedProperties;

    /**
     * @var NodeIdentifier
     * @deprecated
     */
    protected $nodeIdentifier;

    /**
     * @var ContentSubgraphInterface
     * @deprecated
     */
    protected $contentSubgraph;

    /**
     * @var array
     * @deprecated
     */
    protected $references;

    /**
     * @var array
     * @deprecated
     */
    protected $referenceProperties = [];

    /**
     * @var array
     * @deprecated
     */
    protected $referencesProperties = [];

    /**
     * PropertyCollection constructor.
     * @param array $properties
     * @param array $referenceProperties @deprecated
     * @param array $referencesProperties @deprecated
     * @param NodeIdentifier $contentSubgraph @deprecated
     * @param ContentSubgraphInterface $contentSubgraph @deprecated
     */
    public function __construct(array $properties, array $referenceProperties = [], array $referencesProperties = [], NodeIdentifier $nodeIdentifier = null, ContentSubgraphInterface $contentSubgraph = null)
    {
        $this->properties = $properties;

        if ($contentSubgraph && $nodeIdentifier) {
            $this->referenceProperties = array_fill_keys($referenceProperties, true);
            $this->referencesProperties = array_fill_keys($referencesProperties, true);
            $this->nodeIdentifier = $nodeIdentifier;
            $this->contentSubgraph = $contentSubgraph;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (isset($this->properties[$offset])) {
            return true;
        }
        if (isset($this->referenceProperties[$offset]) || isset($this->referencesProperties[$offset])) {
            return true;
        }
        return false;
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (isset($this->properties[$offset])) {
            if (is_array($this->properties[$offset]) && !isset($this->resolvedProperties[$offset])) {
                if (isset($this->properties[$offset]['__flow_object_type'])) {
                    $this->resolveObject($this->properties[$offset]);
                } else {
                    foreach ($this->properties[$offset] as $i => $propertyValue) {
                        if (isset($this->properties[$offset][$i]['__flow_object_type'])) {
                            $this->resolveObject($this->properties[$offset][$i]);
                        }
                    }
                }
                $this->resolvedProperties[$offset] = true;
            }
            return $this->properties[$offset];
        }

        // TODO: reference properties resolvinf
        if (isset($this->referenceProperties[$offset]) || isset($this->referencesProperties[$offset])) {
            if (!isset($this->references[$offset])) {
                $propertyReferences = $this->contentSubgraph->findReferencedNodes($this->nodeIdentifier, new PropertyName($offset));
                if (isset($this->referenceProperties[$offset])) {
                    $this->references[$offset] = count($propertyReferences) ? reset($propertyReferences) : null;
                } else {
                    $this->references[$offset] = $propertyReferences;
                }
            }
            return $this->references[$offset];
        }

        return null;
    }

    /**
     * @param $value
     */
    protected function resolveObject(&$value)
    {
        $value = $this->persistenceManager->getObjectByIdentifier(
            $value['__identifier'],
            $value['__flow_object_type']
        );
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->properties);
    }

    /**
     * @return mixed|null
     */
    public function current()
    {
        return $this->offsetGet($this->key());
    }

    public function next()
    {
        next($this->properties);
    }

    /**
     * @return int|mixed|null|string
     */
    public function key()
    {
        return key($this->properties);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return current($this->properties) !== false;
    }

    /**
     *
     */
    public function rewind()
    {
        reset($this->properties);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getPropertyNames(): array
    {
        return array_keys($this->properties);
    }
}
