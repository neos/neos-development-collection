<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Migration\Filters;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * Filter nodes having the given property and its value not empty.
 */
class PropertyValue implements NodeBasedFilterInterface
{
    /**
     * The property name
     */
    protected string $propertyName;


    protected mixed $serializedValue;

    /**
     * Sets the property name to be checked.
     *
     * @param string $propertyName
     * @return void
     */
    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Sets the property value to be checked against.
     *
     * @param mixed $propertyValue
     * @return void
     */
    public function setSerializedValue(mixed $serializedValue): void
    {
        $this->serializedValue = $serializedValue;
    }

    public function matches(NodeInterface $node): bool
    {
        if (!$node->hasProperty($this->propertyName)) {
            return false;
        }
        $serializedPropertyValue = $node->getProperties()->serialized()->getProperty($this->propertyName);
        if (!$serializedPropertyValue) {
            return false;
        }

        return $this->serializedValue === $serializedPropertyValue->getValue();
    }
}
