<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Tests\Unit;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\PropertyDiff;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PropertyDiffTest extends TestCase
{
    /**
     * @dataProvider propertyComparisonProvider
     */
    public function testFromNodeComparison(
        SerializedPropertyValues $referenceProperties,
        SerializedPropertyValues $propertiesToCompare,
        ?PropertyDiff $expectedPropertyDiff,
    ): void {
        Assert::assertEquals(
            $expectedPropertyDiff,
            PropertyDiff::tryFromNodeComparison($propertiesToCompare, $referenceProperties),
        );
    }

    /**
     * @return iterable<array{
     *     referenceProperties: SerializedPropertyValues,
     *     modifiedProperties: SerializedPropertyValues,
     *     expectedPropertyDiff: PropertyDiff,
     * }>>
     */
    public static function propertyComparisonProvider(): iterable
    {
        $referenceProperties = SerializedPropertyValues::fromArray([
            'text' => SerializedPropertyValue::create(
                value: 'textValue',
                type: 'string',
            ),
            'otherText' => SerializedPropertyValue::create(
                value: 'otherTextValue',
                type: 'string',
            ),
            'textToRemove' => SerializedPropertyValue::create(
                value: 'textToRemoveValue',
                type: 'string',
            ),
        ]);

        yield 'identicalProperties' => [
            'referenceProperties' => $referenceProperties,
            'propertiesToCompare' => $referenceProperties,
            'expectedPropertyDiff' => null,
        ];

        yield 'modifiedProperties' => [
            'referenceProperties' => $referenceProperties,
            'propertiesToCompare' => SerializedPropertyValues::fromArray([
                'text' => SerializedPropertyValue::create(
                    value: 'modifiedTextValue',
                    type: 'string',
                ),
                'otherText' => SerializedPropertyValue::create(
                    value: 'otherTextValue',
                    type: 'string',
                ),
                'addedText' => SerializedPropertyValue::create(
                    value: 'addedTextValue',
                    type: 'string',
                ),
            ]),
            'expectedPropertyDiff' => PropertyDiff::tryCreate(
                addedProperties: SerializedPropertyValues::fromArray([
                    'addedText' => SerializedPropertyValue::create(
                        value: 'addedTextValue',
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
        ];
    }
}
