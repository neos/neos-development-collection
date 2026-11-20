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

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReference;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferencesForName;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * @internal implementation detail of command handlers
 */
trait NodeReferencingInternals
{
    abstract protected function getPropertyConverter(): PropertyConverter;

    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireReferenceType(
        ReferenceTypeName $referenceTypeName,
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName
    ): ReferenceType;

    private function mapNodeReferencesToSerializedNodeReferences(NodeReferencesToWrite $references, NodeTypeName $nodeTypeName): SerializedNodeReferences
    {
        $serializedReferencesByProperty = [];
        foreach ($references as $referencesByProperty) {
            $serializedReferences = [];
            foreach ($referencesByProperty->references as $reference) {
                $serializedReferences[] = SerializedNodeReference::fromTargetAndProperties(
                    targetNodeAggregateId: $reference->targetNodeAggregateId,
                    properties: $this->getPropertyConverter()->serializeReferencePropertyValues(
                        propertyValuesToWrite: $reference->properties,
                        referenceType: $this->requireReferenceType($reference->referenceTypeName, $referencesByProperty->referenceName, $nodeTypeName),
                        nodeType: $this->requireNodeType($nodeTypeName),
                        referenceName: $referencesByProperty->referenceName
                    ),
                    referenceTypeName: $reference->referenceTypeName,
                );
            }

            $serializedReferencesByProperty[] = SerializedNodeReferencesForName::fromSerializedReferences(
                $referencesByProperty->referenceName,
                $serializedReferences
            );
        }

        return SerializedNodeReferences::fromArray($serializedReferencesByProperty);
    }
}
