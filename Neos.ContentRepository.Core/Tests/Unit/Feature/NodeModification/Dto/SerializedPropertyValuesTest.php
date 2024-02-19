<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Feature\NodeModification\Dto;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use PHPUnit\Framework\TestCase;

class SerializedPropertyValuesTest extends TestCase
{
    /**
     * @test
     */
    public function nonExistingPropertyName(): void
    {
        $propertyValues = SerializedPropertyValues::fromArray([]);
        self::assertFalse($propertyValues->propertyExists('someProperty'));
        self::assertNull($propertyValues->getProperty('someProperty'));
    }

    /**
     * @test
     */
    public function nullIsRejectedByFromArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SerializedPropertyValues::fromArray(['someProperty' => null]);
    }

    /**
     * @test
     */
    public function nullIsRejectedByNestedFromArray(): void
    {
        $this->expectException(\TypeError::class);
        // events with the old shape `{"value":null,"type":"string"}` fail
        SerializedPropertyValues::fromArray(['someProperty' => ['value' => null, 'type' => 'string']]);
    }

    /**
     * @test
     */
    public function nullIsRejectedByConstructor(): void
    {
        $this->expectException(\TypeError::class);
        SerializedPropertyValue::create(null, 'string');
    }

    /**
     * @test
     */
    public function jsonSerialize(): void
    {
        // the format which is implicitly used in the event log, due to json_serialize
        $propertyValues = SerializedPropertyValues::fromArray(['otherProperty' => SerializedPropertyValue::create('mhs', 'string')]);
        self::assertJsonStringEqualsJsonString(
            '{"otherProperty":{"value":"mhs","type":"string"}}',
            json_encode($propertyValues)
        );
    }

    /**
     * @test
     */
    public function getPlainValues(): void
    {
        $propertyValues = SerializedPropertyValues::fromArray(['otherProperty' => SerializedPropertyValue::create('me-he-he', 'string')]);
        self::assertSame(
            ['otherProperty' => 'me-he-he'],
            $propertyValues->getPlainValues()
        );
    }

    /**
     * @test
     */
    public function unsetProperties(): void
    {
        $unsetProperties = PropertyNames::fromArray(['someProperty', 'nonExistent']);
        $propertyValues = SerializedPropertyValues::fromArray(['someProperty' => SerializedPropertyValue::create('old value', 'string'), 'otherProperty' => SerializedPropertyValue::create('text', 'string')]);
        self::assertEquals(
            SerializedPropertyValues::fromArray(['otherProperty' => SerializedPropertyValue::create('text', 'string')]),
            $propertyValues->unsetProperties($unsetProperties)
        );

        $propertyValues = SerializedPropertyValues::fromArray(['someProperty' => SerializedPropertyValue::create('text', 'string'), 'otherProperty' => SerializedPropertyValue::create('text', 'string')]);
        self::assertEquals(
            $propertyValues,
            $propertyValues->unsetProperties(PropertyNames::createEmpty())
        );
    }
}
