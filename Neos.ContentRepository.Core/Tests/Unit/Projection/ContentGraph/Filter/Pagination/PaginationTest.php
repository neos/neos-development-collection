<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph\Filter\Pagination;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\Flow\Tests\UnitTestCase;

class PaginationTest extends UnitTestCase
{
    public function invalidLimitAndOffsets(): \Generator
    {
        yield ['limit' => 0, 'offset' => 0];
        yield ['limit' => 1, 'offset' => -1];
        yield ['limit' => -1, 'offset' => 0];
    }

    /**
     * @test
     * @dataProvider invalidLimitAndOffsets
     */
    public function fromLimitAndOffsetChecksConstraints(int $limit, int $offset): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Pagination::fromLimitAndOffset($limit, $offset);
    }

    /**
     * @test
     */
    public function fromArraySetsLimitIfItIsNotSpecified(): void
    {
        $pagination = Pagination::fromArray(['offset' => 123]);
        self::assertSame(PHP_INT_MAX, $pagination->limit);
    }

    /**
     * @test
     */
    public function fromArraySetsOffsetIfItIsNotSpecified(): void
    {
        $pagination = Pagination::fromArray(['limit' => 123]);
        self::assertSame(0, $pagination->offset);
    }

    /**
     * @test
     */
    public function fromArraySetsLimitAndOffsetIfBothAreNotSet(): void
    {
        $pagination = Pagination::fromArray([]);
        self::assertSame(PHP_INT_MAX, $pagination->limit);
        self::assertSame(0, $pagination->offset);
    }

    /**
     * @test
     */
    public function fromArrayCastsNumericStrings(): void
    {
        $pagination = Pagination::fromArray(['limit' => '12', 'offset' => ' 23 ']);
        self::assertSame(12, $pagination->limit);
        self::assertSame(23, $pagination->offset);
    }

    public function invalidLimitAndOffsetArrays(): \Generator
    {
        yield ['no numeric limit' => ['limit' => 'not a number']];
        yield ['no numeric offset' => ['offset' => 'not a number']];
        yield ['limit out of range' => ['limit' => 0, 'offset' => 0]];
        yield ['offset out of range' => ['limit' => 1, 'offset' => -1]];
        yield ['unknown key' => ['unknown' => 123]];
        yield ['unknown keys' => ['limid' => 123, 'offsed' => 123]];
    }

    /**
     * @test
     * @dataProvider invalidLimitAndOffsetArrays
     */
    public function fromArrayThrowsExceptionForInvalidArrays(array $array): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Pagination::fromArray($array);
    }
}
