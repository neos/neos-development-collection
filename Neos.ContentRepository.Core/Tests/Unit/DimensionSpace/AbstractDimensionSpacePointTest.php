<?php

namespace Neos\ContentRepository\Core\Tests\Unit\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\AbstractDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit test cases for the coordinate validation of dimension space points
 */
class AbstractDimensionSpacePointTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<AbstractDimensionSpacePoint>}>
     */
    public static function dimensionSpacePointClasses(): iterable
    {
        yield 'DimensionSpacePoint' => [DimensionSpacePoint::class];
        yield 'OriginDimensionSpacePoint' => [OriginDimensionSpacePoint::class];
    }

    /**
     * @return iterable<string, array{class-string<AbstractDimensionSpacePoint>, string}>
     */
    public static function invalidCoordinates(): iterable
    {
        foreach (self::dimensionSpacePointClasses() as $className => [$class]) {
            yield $className . ': empty dimension name' => [$class, '{"":"de"}'];
            yield $className . ': integer dimension name' => [$class, '{"0":"de"}'];
            yield $className . ': empty dimension value' => [$class, '{"language":""}'];
            yield $className . ': integer dimension value' => [$class, '{"language":1}'];
            yield $className . ': null dimension value' => [$class, '{"language":null}'];
            yield $className . ': nested dimension value' => [$class, '{"language":{"de":"at"}}'];
            yield $className . ': list' => [$class, '["de"]'];
        }
    }

    /**
     * @param class-string<AbstractDimensionSpacePoint> $class
     */
    #[DataProvider('invalidCoordinates')]
    public function testInvalidCoordinatesAreRejected(string $class, string $json): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1506076562);
        $class::fromJsonString($json);
    }

    /**
     * @param class-string<AbstractDimensionSpacePoint> $class
     */
    #[DataProvider('dimensionSpacePointClasses')]
    public function testValidCoordinatesAreAccepted(string $class): void
    {
        $dimensionSpacePoint = $class::fromArray(['language' => 'de', 'country' => 'at']);
        self::assertSame(['language' => 'de', 'country' => 'at'], $dimensionSpacePoint->coordinates);
        self::assertTrue($class::schema()->validate($dimensionSpacePoint->jsonSerialize())->valid);
    }

    /**
     * @param class-string<AbstractDimensionSpacePoint> $class
     */
    #[DataProvider('dimensionSpacePointClasses')]
    public function testAPointWithoutDimensionsIsAcceptedAndSerializedAsEmptyList(string $class): void
    {
        $dimensionSpacePoint = $class::createWithoutDimensions();
        self::assertSame([], $dimensionSpacePoint->coordinates);
        self::assertSame('[]', $dimensionSpacePoint->toJson());
        self::assertTrue($class::fromJsonString('{}')->equals($dimensionSpacePoint));
    }

    public function testSchemaDescribesAMapOfNonEmptyStrings(): void
    {
        $schema = json_decode(json_encode(AbstractDimensionSpacePoint::schema(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('object', $schema['type']);
        self::assertSame(['type' => 'string', 'minLength' => 1], $schema['propertyNames']);
        self::assertSame(['type' => 'string', 'minLength' => 1], $schema['additionalProperties']);
    }
}
