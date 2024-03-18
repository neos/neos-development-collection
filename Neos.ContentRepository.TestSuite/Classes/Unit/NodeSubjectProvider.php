<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Unit;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ArrayNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\CollectionTypeDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ScalarNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\UriNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectArrayDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectBoolDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectFloatDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectIntDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectStringDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * The general provider class for node subjects.
 * Capable of creating all kinds of nodes to be used in unit tests.
 *
 * @internal this WIP helper is purely experimental and only to be used internally
 *           behaviour or api may change at any time.
 *           Generally It's advised to prefer behat testing over unit tests for complex cases,
 *           like when interacting with the NodeType or the Subgraph or other parts of the CR.
 */
final class NodeSubjectProvider
{
    public PropertyConverter $propertyConverter;

    public function __construct()
    {
        $this->propertyConverter = new PropertyConverter(
            new Serializer([
                new DateTimeNormalizer(),
                new ScalarNormalizer(),
                new BackedEnumNormalizer(),
                new ArrayNormalizer(),
                new UriNormalizer(),
                new ValueObjectArrayDenormalizer(),
                new ValueObjectBoolDenormalizer(),
                new ValueObjectFloatDenormalizer(),
                new ValueObjectIntDenormalizer(),
                new ValueObjectStringDenormalizer(),
                new CollectionTypeDenormalizer()
            ])
        );
    }

    public function usePropertyConverter(PropertyConverter $propertyConverter): void
    {
        $this->propertyConverter = $propertyConverter;
    }

    public function createMinimalNodeOfType(
        NodeType $nodeType,
        SerializedPropertyValues $propertyValues = null,
        ?NodeName $nodeName = null
    ): Node {
        $serializedDefaultPropertyValues = SerializedPropertyValues::defaultFromNodeType($nodeType, $this->propertyConverter);
        return Node::create(
            ContentSubgraphIdentity::create(
                ContentRepositoryId::fromString('default'),
                ContentStreamId::fromString('cs-id'),
                DimensionSpacePoint::createWithoutDimensions(),
                VisibilityConstraints::withoutRestrictions()
            ),
            NodeAggregateId::create(),
            OriginDimensionSpacePoint::createWithoutDimensions(),
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            $nodeType->name,
            $nodeType,
            new PropertyCollection(
                $propertyValues
                    ? $serializedDefaultPropertyValues->merge($propertyValues)
                    : $serializedDefaultPropertyValues,
                $this->propertyConverter
            ),
            $nodeName,
            NodeTags::createEmpty(),
            Timestamps::create(
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
                new \DateTimeImmutable()
            )
        );
    }
}
