<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollectionInterface;

/**
 * Filter nodes having the given property and its value not empty.
 */
class PropertyValueFilterFactory implements FilterFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(array $settings): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface
    {
        return new class ($settings['propertyName'], $settings['serializedValue']) implements NodeBasedFilterInterface {
            public function __construct(
                /**
                 * The property name to be checked
                 */
                private readonly ?string $propertyName,
                /**
                 * The property value to be checked against
                 */
                private readonly mixed $serializedValue,
            ) {
            }

            public function matches(Node $node): bool
            {
                if (is_null($this->propertyName) || !$node->hasProperty($this->propertyName)) {
                    return false;
                }
                /** @var PropertyCollectionInterface $properties */
                $properties = $node->properties;
                $serializedPropertyValue = $properties->serialized()->getProperty($this->propertyName);
                if (!$serializedPropertyValue) {
                    return false;
                }

                return $this->serializedValue === $serializedPropertyValue->value;
            }
        };
    }
}
