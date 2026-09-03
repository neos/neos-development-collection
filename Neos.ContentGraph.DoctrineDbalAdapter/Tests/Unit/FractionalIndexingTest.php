<?php
declare(strict_types=1);

namespace Neos\ContentGraph\Tests\Unit;

use Neos\ContentGraph\DoctrineDbalAdapter\FractionalIndexing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FractionalIndexingTest extends TestCase
{

    #[DataProvider('dataForTestGenerateKeyBetween')]
    #[Test]
    public function testGenerateKeyBetween($a, $b, $expected)
    {
        $this->assertEquals($expected, FractionalIndexing::generateKeyBetween($a, $b));
    }

    public static function dataForTestGenerateKeyBetween()
    {
        return [
            [null, null, 'a0'],
            [null, 'a0', 'Zz'],
            ["a0", null, "a1"],
            ["a0", "a1", "a0V"],
            ["a0V", "a1", "a0l"],
            ["Zz", "a0", "ZzV"],
            ["Zz", "a1", "a0"],
            [null, "Y00", "Xzzz"],
            ["bzz", null, "c000"],
            ["a0", "a0V", "a0G"],
            ["a0", "a0G", "a08"],
            ["b125", "b129", "b127"],
            ["a0", "a1V", "a1"],
            ["Zz", "a01", "a0"],
            [null, "a0V", "a0"],
            [null, "b999", "b99"],
            [null, "A000000000000000000000000001", "A000000000000000000000000000V"],
            ["zzzzzzzzzzzzzzzzzzzzzzzzzzy", null, "zzzzzzzzzzzzzzzzzzzzzzzzzzz"],
            ["zzzzzzzzzzzzzzzzzzzzzzzzzzz", null, "zzzzzzzzzzzzzzzzzzzzzzzzzzzV"],
            ["ZzI", "ZzIV", "ZzIG"],
            ["d153V", "d3B81", "d153W"],
            ["d153W", "d3B81", "d153X"],
            ["d153X", "d3B81", "d153Y"],
            ["d153Y", "d3B81", "d153Z"],
        ];
    }

    #[DataProvider('dataForTestGenerateKeyBetweenValidation')]
    #[Test]
    public function testGenerateKeyBetweenValidation($a, $b)
    {
        $this->expectException(\RuntimeException::class);
        FractionalIndexing::generateKeyBetween($a, $b);
    }

    public static function dataForTestGenerateKeyBetweenValidation()
    {
        return [
            [null, "A00000000000000000000000000"],
            ['a00', null],
            ['a00', 'a1'],
            ['0', '1'],
            ['a1', 'a0'],
        ];
    }

    #[DataProvider('dataForTestGenerateKeyBetweenInsertAlwaysBefore')]
    #[Test]
    public function testGenerateKeyBetweenInsertAlwaysBefore(int $iterations, string $expected)
    {

        $a = null;
        $b = null;

        for ($i = 0; $i < $iterations; $i++) {
            $b = FractionalIndexing::generateKeyBetween($a, $b);
        }

        $this->assertEquals($expected, $b);
    }

    public static function dataForTestGenerateKeyBetweenInsertAlwaysBefore()
    {
        yield [1, 'a0'];
        yield [10, 'Zr'];
        yield [100, 'YzP'];
        yield [1000, 'Ykt'];
        yield [10000, 'XyPj'];
        yield [100000, 'Xb07'];
        yield [1000000, 'Wworz'];
    }

    #[DataProvider('dataForTestGenerateKeyBetweenInsertAlwaysAfter')]
    #[Test]
    public function testGenerateKeyBetweenInsertAlwaysAfter(int $iterations, string $expected)
    {

        $a = null;
        $b = null;

        for ($i = 0; $i < $iterations; $i++) {
            $a = FractionalIndexing::generateKeyBetween($a, $b);
        }

        $this->assertEquals($expected, $a);
    }

    public static function dataForTestGenerateKeyBetweenInsertAlwaysAfter()
    {
        yield [1, 'a0'];
        yield [10, 'a9'];
        yield [100, 'b0b'];
        yield [1000, 'bF7'];
        yield [10000, 'c1aH'];
        yield [100000, 'cOzt'];
        yield [1000000, 'd3B81'];
    }

    #[DataProvider('dataForTestGenerateKeyBetweenInsertAlwaysIntoSameGap')]
    #[Test]
    public function testGenerateKeyBetweenInsertAlwaysIntoSameGap(int $iterations, string $expected)
    {

        $a = FractionalIndexing::generateKeyBetween(null, null);
        $b = FractionalIndexing::generateKeyBetween($a, null);

        for ($i = 0; $i < $iterations; $i++) {
            $b = FractionalIndexing::generateKeyBetween($a, $b);
        }

        $this->assertEquals($expected, $b);
    }

    public static function dataForTestGenerateKeyBetweenInsertAlwaysIntoSameGap()
    {
        yield [1, 'a0V']; // 3 chars
        yield [10, 'a004']; // 4 chars
        yield [100, 'a000000000000000004']; // 19 chard
        yield [128, 'a0000000000000000000000G']; // 24 chars
        // 169 chars
        yield [1000, 'a000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000004'];
        // 1669 chars
        yield [10000, 'a000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000004'];
    }

    #[DataProvider('dataForTestGenerateNKeysBetween')]
    #[Test]
    public function testGenerateNKeysBetween($a, $b, $numberOfKeys, $expectedLowestKey, $expectedMiddleKey, $expectedHighestKey)
    {

        $keys = FractionalIndexing::generateNKeysBetween($a, $b, $numberOfKeys);

        $this->assertCount($numberOfKeys, $keys);
        $this->assertEquals($expectedLowestKey, $keys[0], 'Lowest key should be the same');
        $this->assertEquals($expectedMiddleKey, $keys[ceil((count($keys) / 2) - 1) ], 'Middle key should be the same');
        $this->assertEquals($expectedHighestKey, $keys[count($keys) - 1], 'Highest key should be the same');
    }

    public static function dataForTestGenerateNKeysBetween()
    {
        yield [null, null, 10, 'a0', 'a4', 'a9']; // 2 chars
        yield [null, null, 100, 'a0', 'an', 'b0b']; // 3 chars
        yield [null, null, 1000, 'a0', 'b73', 'bF7']; // 3 chars
        yield [null, null, 10000, 'a0', 'c0Hd', 'c1aH']; // 4 chars
        yield [null, null, 100000, 'a0', 'cBzR', 'cOzt']; // 4 chars
        yield [null, null, 1000000, 'a0', 'd153V', 'd3B81']; // 5 chars
    }
}