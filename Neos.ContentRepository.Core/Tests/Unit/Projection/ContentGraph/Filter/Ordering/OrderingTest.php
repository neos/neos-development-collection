<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph\Filter\Ordering;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\TimestampField;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\Flow\Tests\UnitTestCase;

class OrderingTest extends UnitTestCase
{
    /**
     * @test
     */
    public function byPropertyTest(): void
    {
        $ordering = Ordering::byProperty(PropertyName::fromString('someProperty'), OrderingDirection::ASCENDING)
            ->andByTimestampField(TimestampField::CREATED, OrderingDirection::ASCENDING)
            ->andByProperty(PropertyName::fromString('someOtherProperty'), OrderingDirection::DESCENDING)
            ->andByTimestampField(TimestampField::ORIGINAL_LAST_MODIFIED, OrderingDirection::DESCENDING);
        $expectedRepresentation = [
            ['type' => 'propertyName', 'field' => 'someProperty', 'direction' => 'ASCENDING'],
            ['type' => 'timestampField', 'field' => 'CREATED', 'direction' => 'ASCENDING'],
            ['type' => 'propertyName', 'field' => 'someOtherProperty', 'direction' => 'DESCENDING'],
            ['type' => 'timestampField', 'field' => 'ORIGINAL_LAST_MODIFIED', 'direction' => 'DESCENDING'],
        ];
        self::assertOrderingEquals($expectedRepresentation, $ordering);
    }

    /**
     * @test
     */
    public function byPropertyCreatesInstance(): void
    {
        $ordering = Ordering::byTimestampField(TimestampField::LAST_MODIFIED, OrderingDirection::DESCENDING);
        $expectedRepresentation = [
            ['type' => 'timestampField', 'field' => 'LAST_MODIFIED', 'direction' => 'DESCENDING'],
        ];
        self::assertOrderingEquals($expectedRepresentation, $ordering);
    }

    /**
     * @test
     */
    public function iterationTest(): void
    {
        $ordering = Ordering::byProperty(PropertyName::fromString('someProperty'), OrderingDirection::ASCENDING)
            ->andByTimestampField(TimestampField::CREATED, OrderingDirection::ASCENDING)
            ->andByProperty(PropertyName::fromString('someOtherProperty'), OrderingDirection::DESCENDING)
            ->andByTimestampField(TimestampField::ORIGINAL_LAST_MODIFIED, OrderingDirection::DESCENDING);
        self::assertCount(4, $ordering);
    }

    /**
     * @test
     */
    public function fromArrayTest(): void
    {
        $array = [
            ['type' => 'propertyName', 'field' => 'someProperty', 'direction' => 'ASCENDING'],
            ['type' => 'timestampField', 'field' => 'CREATED', 'direction' => 'ASCENDING'],
            ['type' => 'propertyName', 'field' => 'someOtherProperty', 'direction' => 'DESCENDING'],
            ['type' => 'timestampField', 'field' => 'ORIGINAL_LAST_MODIFIED', 'direction' => 'DESCENDING'],
        ];
        $ordering = Ordering::fromArray($array);

        self::assertOrderingEquals($array, $ordering);
    }

    public function invalidOrderingArrays(): \Generator
    {
        yield ['empty array' => []];
        yield ['empty nested array' => [[]]];
        yield ['missing type' => [['field' => 'somePropertyName', 'direction' => 'ASCENDING']]];
        yield ['missing field' => [['type' => 'propertyName', 'direction' => 'ASCENDING']]];
        yield ['missing direction' => [['type' => 'propertyName', 'field' => 'somePropertyName']]];
        yield ['invalid type' => [['type' => 'invalid', 'field' => 'somePropertyName', 'direction' => 'ASCENDING']]];
        yield ['invalid property name' => [['type' => 'propertyName', 'field' => '', 'direction' => 'ASCENDING']]];
        yield ['invalid timestamp field' => [['type' => 'timestampField', 'field' => 'INVALID', 'direction' => 'ASCENDING']]];
        yield ['invalid direction' => [['type' => 'propertyName', 'field' => 'propertyName', 'direction' => 'INVALID']]];
        yield ['unknown element' => [['type' => 'propertyName', 'field' => 'propertyName', 'direction' => 'ASCENDING', 'unknown' => 'element']]];
    }

    /**
     * @test
     * @dataProvider invalidOrderingArrays
     */
    public function fromArrayThrowsExceptionForInvalidArrays(array $array): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Ordering::fromArray($array);
    }

    private static function assertOrderingEquals(array $expectedOrderingRepresentation, Ordering $actualOrdering): void
    {
        try {
            self::assertSame($expectedOrderingRepresentation, json_decode(json_encode($actualOrdering, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to encode/decode ordering: %s', $e->getMessage()), 1680270292, $e);
        }
    }
}
