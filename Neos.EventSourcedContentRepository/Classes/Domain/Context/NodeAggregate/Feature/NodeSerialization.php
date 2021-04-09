<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Intermediary\Domain\Command\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;

trait NodeSerialization
{
    private function serializeProperties(?PropertyValuesToWrite $propertyValues, NodeTypeName $nodeTypeName): ?SerializedPropertyValues
    {
        if (!$propertyValues) {
            return null;
        }
        $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeTypeName);

        return $this->propertyConverter->serializePropertyValues($propertyValues, $nodeType);
    }

    private function unserializeDefaultProperties(NodeTypeName $nodeTypeName): PropertyValuesToWrite
    {
        $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeTypeName);
        $defaultValues = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $defaultValue) {
            $propertyType = PropertyType::fromNodeTypeDeclaration($nodeType->getPropertyType($propertyName));
            $defaultValues[$propertyName] = $this->propertyConverter->deserializePropertyValue(
                new SerializedPropertyValue($defaultValue, $propertyType->getSerializationType())
            );
        }

        return PropertyValuesToWrite::fromArray($defaultValues);
    }
}
