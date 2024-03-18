<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

class PropertyCollectionTest extends TestCase
{

    private Serializer|MockObject $mockSerializer;
    private PropertyConverter|MockObject $mockPropertyConverter;

    public function setUp(): void
    {
        $this->mockSerializer = $this->getMockBuilder(Serializer::class)->disableOriginalConstructor()->getMock();
        $this->mockPropertyConverter = new PropertyConverter($this->mockSerializer);
    }

    /**
     * @test
     */
    public function emptyPropertyCollectionReturnsEmptyArray(): void
    {
        $this->mockSerializer->expects($this->never())->method($this->anything());
        $collection = new PropertyCollection(SerializedPropertyValues::createEmpty(), $this->mockPropertyConverter);
        self::assertSame([], iterator_to_array($collection));
    }

    /**
     * @test
     */
    public function offsetGetReturnsNullIfPropertyDoesNotExist(): void
    {
        $this->mockSerializer->expects($this->never())->method($this->anything());
        $collection = new PropertyCollection(SerializedPropertyValues::fromArray(['someProperty' => ['value' => 'some string', 'type' => 'string']]), $this->mockPropertyConverter);
        self::assertNull($collection['non-existing']);
        self::assertFalse(isset($collection['non-existing']));
    }

    /**
     * @test
     */
    public function offsetGetReturnsDeserializedValue(): void
    {
        $this->mockSerializer->expects($this->once())->method('denormalize')->with('some string', 'string', null, [])->willReturn('some deserialized value');
        $collection = new PropertyCollection(SerializedPropertyValues::fromArray(['someProperty' => ['value' => 'some string', 'type' => 'string']]), $this->mockPropertyConverter);
        self::assertSame('some deserialized value', $collection['someProperty']);
        self::assertTrue(isset($collection['someProperty']));
    }

    /**
     * @test
     */
    public function deserializedValueMightEvaluateToNull(): void
    {
        $this->mockSerializer->expects($this->once())->method('denormalize')
            ->with('protected-image-123', 'ImageEntity', null, [])
            ->willReturn(null);

        $collection = new PropertyCollection(
            SerializedPropertyValues::fromArray([
                'myImage' => [
                    'value' => 'protected-image-123',
                    'type' => 'ImageEntity'
                ]
            ]),
            $this->mockPropertyConverter
        );

        // $node->hasProperty("myImage") will be true
        self::assertTrue(isset($collection['myImage']));

        // An entity privilege would result in a referenced object being null if the user reading
        // the node doesn't have permission to see it.
        // $node->getProperty("myImage") will be null!!!
        self::assertNull($collection['myImage']);
    }

    /**
     * @test
     */
    public function propertiesCanBeIterated(): void
    {
        $this->mockSerializer->expects($this->once())->method('denormalize')->with('some string', 'string', null, [])->willReturn('some deserialized value');
        $collection = new PropertyCollection(SerializedPropertyValues::fromArray(['someProperty' => ['value' => 'some string', 'type' => 'string']]), $this->mockPropertyConverter);
        self::assertSame(['someProperty' => 'some deserialized value'], iterator_to_array($collection));
    }

    /**
     * @test
     */
    public function offsetSetThrowsAnException(): void
    {
        $collection = new PropertyCollection(SerializedPropertyValues::createEmpty(), $this->mockPropertyConverter);
        $this->expectException(\RuntimeException::class);
        $collection->offsetSet('foo', 'bar');
    }

    /**
     * @test
     */
    public function offsetUnsetThrowsAnException(): void
    {
        $collection = new PropertyCollection(SerializedPropertyValues::createEmpty(), $this->mockPropertyConverter);
        $this->expectException(\RuntimeException::class);
        $collection->offsetUnset('foo');
    }

    /**
     * @test
     */
    public function serializedReturnsSerializedPropertyValues(): void
    {
        $serializedPropertyValues = SerializedPropertyValues::fromArray(['someProperty' => ['value' => 'some string', 'type' => 'string']]);
        $collection = new PropertyCollection($serializedPropertyValues, $this->mockPropertyConverter);
        self::assertEquals($serializedPropertyValues, $collection->serialized());
    }
}
