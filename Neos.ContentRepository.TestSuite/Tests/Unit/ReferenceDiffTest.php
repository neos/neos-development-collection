<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Tests\Unit;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\NodeDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\PropertyDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\ReferenceDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\SampleNodeFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ReferenceDiffTest extends TestCase
{
    /**
     * @dataProvider referenceComparisonProvider
     */
    public function testFromNodeComparison(Reference $referenceReference, Reference $referenceToCompare, ?ReferenceDiff $expectedReferenceDiff): void
    {
        Assert::assertEquals(
            $expectedReferenceDiff,
            ReferenceDiff::tryFromReferenceComparison($referenceToCompare, $referenceReference, null)
        );
    }

    public static function referenceComparisonProvider(): iterable
    {
        $referenceReference = SampleNodeFactory::createSampleReference();

        yield 'identicalReferences' => [
            'referenceReference' => $referenceReference,
            'referenceToCompare' => $referenceReference,
            'expectedReferenceDiff' => null,
        ];

        yield 'referenceWithUnsetProperties' => [
            'referenceReference' => $referenceReference,
            'referenceToCompare' => SampleNodeFactory::modifyReferenceWith(
                reference: $referenceReference,
                unsetProperties: true,
            ),
            'expectedReferenceDiff' => ReferenceDiff::tryCreate(
                propertiesWereUnset: true,
            )
        ];

        yield 'differingReference' => [
            'referenceReference' => $referenceReference,
            'referenceToCompare' => SampleNodeFactory::modifyReferenceWith(
                reference: $referenceReference,
                node: SampleNodeFactory::modifyNodeWith(
                    node: $referenceReference->node,
                    aggregateId: NodeAggregateId::fromString('other-mc-nodeface'),
                ),
                name: ReferenceName::fromString('other-reference'),
                properties: SerializedPropertyValues::fromArray([
                    'text' => SerializedPropertyValue::create(
                        value: 'modifiedTextValue',
                        type: 'string',
                    ),
                    'otherText' => SerializedPropertyValue::create(
                        value: 'otherTextValue',
                        type: 'string',
                    ),
                    'additionalText' => SerializedPropertyValue::create(
                        value: 'additionalTextValue',
                        type: 'string',
                    ),
                ]),
            ),
            'expectedReferenceDiff' => ReferenceDiff::tryCreate(
                node: NodeDiff::tryCreate(
                    discriminator: NodeAggregateId::fromString('other-mc-nodeface'),
                    aggregateId: NodeAggregateId::fromString('other-mc-nodeface'),
                ),
                name: ReferenceName::fromString('other-reference'),
                properties: PropertyDiff::tryCreate(
                    addedProperties: SerializedPropertyValues::fromArray([
                        'additionalText' => SerializedPropertyValue::create(
                            value: 'additionalTextValue',
                            type: 'string',
                        ),
                    ]),
                    modifiedProperties: SerializedPropertyValues::fromArray([
                        'text' => SerializedPropertyValue::create(
                            value: 'modifiedTextValue',
                            type: 'string',
                        ),
                    ]),
                    removedProperties: PropertyNames::fromArray(['textToRemove']),
                ),
            )
        ];
    }
}
