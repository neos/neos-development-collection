<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\SharedModel\Node;

use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PropertyNamesTest extends TestCase
{
    public static function differenceDataProvider(): iterable
    {
        yield 'empty' => ['names1' => [], 'names2' => [], 'expectedResult' => []];
        yield 'one empty' => ['names1' => [], 'names2' => ['foo'], 'expectedResult' => []];
        yield 'two empty' => ['names1' => ['foo'], 'names2' => [], 'expectedResult' => ['foo']];
        yield 'no intersection' => ['names1' => ['foo', 'bar'], 'names2' => ['baz', 'foos'], 'expectedResult' => ['foo', 'bar']];
        yield 'with intersection' => ['names1' => ['foo', 'bar', 'baz'], 'names2' => ['baz', 'bars', 'foo'], 'expectedResult' => ['bar']];
        yield 'with intersection reversed' => ['names1' => ['baz', 'bars', 'foo'], 'names2' => ['foo', 'bar', 'baz'], 'expectedResult' => ['bars']];
    }

    #[DataProvider('differenceDataProvider')]
    #[Test]
    public function getDifference(array $names1, array $names2, array $expectedResult): void
    {
        self::assertSame($expectedResult, array_column(iterator_to_array(PropertyNames::fromArray($names1)->getDifference(PropertyNames::fromArray($names2))), 'value'));
    }
}
