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

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\UnsetPropertyValue;
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
    public function explicitNullValue(): void
    {
        $propertyValues = SerializedPropertyValues::fromArray(['someProperty' => null]);
        self::assertTrue($propertyValues->propertyExists('someProperty'));
        self::assertSame(UnsetPropertyValue::get(), $propertyValues->getProperty('someProperty'));

        $propertyValues = SerializedPropertyValues::fromArray(['someProperty' => UnsetPropertyValue::get()]);
        self::assertTrue($propertyValues->propertyExists('someProperty'));
        self::assertSame(UnsetPropertyValue::get(), $propertyValues->getProperty('someProperty'));
    }

    /**
     * @test
     */
    public function implicitNullValue(): void
    {
        // contains migration layer for events with properties in alternate / old shape `{"value":null,"type":"string"}`
        $propertyValues = SerializedPropertyValues::fromArray(['someProperty' => ['value' => null, 'type' => 'string']]);
        self::assertSame(UnsetPropertyValue::get(), $propertyValues->getProperty('someProperty'));

        $unset = SerializedPropertyValue::create(null, 'string');
        self::assertSame(UnsetPropertyValue::get(), $unset);

        $unset = SerializedPropertyValue::fromArray(['value' => null, 'type' => 'string']);
        self::assertSame(UnsetPropertyValue::get(), $unset);
    }

    /**
     * @test
     */
    public function jsonSerializeReturnsNullForUnsetValues(): void
    {
        // the format which is implicitly used in the event log, due to json_serialize
        $propertyValues = SerializedPropertyValues::fromArray(['someProperty' => UnsetPropertyValue::get(), 'otherProperty' => SerializedPropertyValue::create('mhs', 'string')]);
        self::assertJsonStringEqualsJsonString(
            '{"someProperty":null,"otherProperty":{"value":"mhs","type":"string"}}',
            json_encode($propertyValues)
        );
    }

    /**
     * @test
     */
    public function getPlainValuesReturnsNullForUnsetValues(): void
    {
        $propertyValues = SerializedPropertyValues::fromArray(['someProperty' => UnsetPropertyValue::get(), 'otherProperty' => SerializedPropertyValue::create('mehehe', 'string')]);
        self::assertSame(
            ['someProperty' => null, 'otherProperty' => 'mehehe'],
            $propertyValues->getPlainValues()
        );
    }
}
