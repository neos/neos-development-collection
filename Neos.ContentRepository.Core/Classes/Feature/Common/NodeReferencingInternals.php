<?php
namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferenceNameToEmpty;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReference;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 *
 */
trait NodeReferencingInternals
{
    abstract protected function getPropertyConverter(): PropertyConverter;
    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    private function mapNodeReferencesToSerializedNodeReferences(NodeReferencesToWrite $references, NodeTypeName $nodeTypeName): SerializedNodeReferences
    {
        $serializedReferences = [];
        foreach ($references->getIterator() as $reference) {
            if ($reference instanceof NodeReferenceNameToEmpty) {
                $serializedReferences[] = $reference;
                continue;
            }

            $serializedReferences[] = new SerializedNodeReference(
                $reference->referenceName,
                $reference->targetNodeAggregateId,
                $reference->properties
                    ? $this->getPropertyConverter()->serializeReferencePropertyValues(
                    $reference->properties,
                    $this->requireNodeType($nodeTypeName),
                    $reference->referenceName
                ) : null
            );
        }

        return SerializedNodeReferences::fromReferences($serializedReferences);
    }
}
