<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Filter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Content\PropertyCollectionInterface;

/**
 * Filter nodes having the given property and its value not empty.
 */
class PropertyValue implements NodeBasedFilterInterface
{
    /**
     * The property name
     */
    protected ?string $propertyName;

    protected mixed $serializedValue;

    /**
     * Sets the property name to be checked.
     */
    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Sets the property value to be checked against.
     */
    public function setSerializedValue(mixed $serializedValue): void
    {
        $this->serializedValue = $serializedValue;
    }

    public function matches(NodeInterface $node): bool
    {
        if (is_null($this->propertyName) || !$node->hasProperty($this->propertyName)) {
            return false;
        }
        /** @var PropertyCollectionInterface $properties */
        $properties = $node->getProperties();
        $serializedPropertyValue = $properties->serialized()->getProperty($this->propertyName);
        if (!$serializedPropertyValue) {
            return false;
        }

        return $this->serializedValue === $serializedPropertyValue->getValue();
    }
}
