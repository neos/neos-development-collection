<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Unit\Dimension;

use Neos\ContentRepository\Dimension;
use Neos\ContentRepository\Dimension\Exception\ContentDimensionValueSpecializationDepthIsInvalid;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for content dimension value specialization depths
 */
class ContentDimensionValueSpecializationDepthTest extends UnitTestCase
{
    public function testInitializationThrowsExceptionForNegativeDepth()
    {
        $this->expectException(ContentDimensionValueSpecializationDepthIsInvalid::class);
        new Dimension\ContentDimensionValueSpecializationDepth(random_int(PHP_INT_MIN, -1));
    }

    public function testIsGreaterThanReturnsTrueForGreaterValue()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(1);

        $this->assertSame(
            true,
            $subject->isGreaterThan(new Dimension\ContentDimensionValueSpecializationDepth(0))
        );
    }

    public function testIsGreaterThanReturnsFalseForLesserValue()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(0);

        $this->assertSame(
            false,
            $subject->isGreaterThan(new Dimension\ContentDimensionValueSpecializationDepth(1))
        );
    }

    public function testIsGreaterThanReturnsFalseForEqualValue()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(0);

        $this->assertSame(
            false,
            $subject->isGreaterThan(new Dimension\ContentDimensionValueSpecializationDepth(0))
        );
    }

    public function testIsZeroReturnsTrueForDepthZero()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(0);

        $this->assertSame(
            true,
            $subject->isZero()
        );
    }

    public function testIsZeroReturnsFalseForDepthGreaterZero()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(random_int(1, PHP_INT_MAX));

        $this->assertSame(
            false,
            $subject->isZero()
        );
    }
}
