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

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Dto;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * "Raw" / Serialized node reference as saved in the event log // in projections.
 *
 * @api used in commands and events {@see SerializedNodeReferences}
 */
final readonly class SerializedNodeReference implements \JsonSerializable
{
    private function __construct(
        public NodeAggregateId $targetNodeAggregateId,
        public ReferenceTypeName $referenceTypeName,
        public SerializedPropertyValues $properties,
    ) {
    }

    public static function fromTargetAndProperties(NodeAggregateId $targetNodeAggregateId, SerializedPropertyValues $properties, ?ReferenceTypeName $referenceTypeName = null): self
    {
        return new self($targetNodeAggregateId, $referenceTypeName ?: ReferenceTypeName::fromString(ReferenceTypeName::FEATURELESS_REFERENCE_TYPE_NAME), $properties);
    }

    public static function fromTarget(NodeAggregateId $targetNodeAggregateId, ?ReferenceTypeName $referenceTypeName = null): self
    {
        return new self($targetNodeAggregateId, $referenceTypeName ?: ReferenceTypeName::fromString(ReferenceTypeName::FEATURELESS_REFERENCE_TYPE_NAME), SerializedPropertyValues::createEmpty());
    }

    /**
     * @param array{"target": string, "properties"?: array<string, mixed>, "referenceTypeName"?: string} $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            targetNodeAggregateId: NodeAggregateId::fromString($array['target']),
            referenceTypeName: ReferenceTypeName::fromString(
                $array['referenceTypeName'] ?? ReferenceTypeName::FEATURELESS_REFERENCE_TYPE_NAME
            ),
            properties: isset($array['properties']) ? SerializedPropertyValues::fromArray($array['properties']) : SerializedPropertyValues::createEmpty()
        );
    }

    public function mergeDefaultReferencePropertyValues(ReferenceTypeGraph $referenceTypeGraph, PropertyConverter $propertyConverter): self
    {
        $referenceType = $referenceTypeGraph->getReferenceType($this->referenceTypeName);

        return $referenceType
            ? new self(
                $this->targetNodeAggregateId,
                $this->referenceTypeName,
                SerializedPropertyValues::defaultFromReferenceType($referenceType, $propertyConverter)
                    ->merge($this->properties),
            )
            : $this;
    }

    public function mergeTighterScopedCurrentReferenceValues(
        PropertyScope $appliedScope,
        ?Reference $currentReference,
        ReferenceTypeGraph $referenceTypeGraph,
    ): self {
        if (!$currentReference) {
            return $this;
        }
        $referenceType = $referenceTypeGraph->getReferenceType($this->referenceTypeName);
        if (!$referenceType) {
            return $this;
        }

        $tighterScopedReferenceValues = [];
        foreach ($referenceType->properties as $property) {
            if ($property->scope->isTighterThan($appliedScope)) {
                $tighterScopedReferenceValues[$property->name->value]
                    = iterator_to_array($currentReference->properties?->serialized() ?: [])[$property->name->value] ?? null;
            }
        }

        return $tighterScopedReferenceValues !== []
            ? new self(
                $this->targetNodeAggregateId,
                $this->referenceTypeName,
                $this->properties->merge(SerializedPropertyValues::fromArray(array_filter($tighterScopedReferenceValues))),
            )
            : $this;
    }

    /**
     * @return array{"target": NodeAggregateId, "referenceTypeName": ReferenceTypeName, "properties"?: SerializedPropertyValues}
     */
    public function jsonSerialize(): array
    {
        $result = [
            'target' => $this->targetNodeAggregateId,
            'referenceTypeName' => $this->referenceTypeName,
        ];
        if (count($this->properties) > 0) {
            $result['properties'] = $this->properties;
        }
        return $result;
    }
}
