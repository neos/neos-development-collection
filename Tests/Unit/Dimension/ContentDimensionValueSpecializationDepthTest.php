<?php

namespace Neos\ContentRepository\DimensionSpace\Tests\Unit\Dimension;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\ContentRepository\DimensionSpace\Dimension\Exception\ContentDimensionValueSpecializationDepthIsInvalid;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for content dimension value specialization depths
 */
class ContentDimensionValueSpecializationDepthTest extends UnitTestCase
{
    /**
     * @test
     */
    public function initializationThrowsExceptionForNegativeDepth()
    {
        $this->expectException(ContentDimensionValueSpecializationDepthIsInvalid::class);
        new Dimension\ContentDimensionValueSpecializationDepth(random_int(PHP_INT_MIN, -1));
    }

    /**
     * @test
     */
    public function isGreaterThanReturnsTrueForGreaterValue()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(1);

        $this->assertSame(
            true,
            $subject->isGreaterThan(new Dimension\ContentDimensionValueSpecializationDepth(0))
        );
    }

    /**
     * @test
     */
    public function isGreaterThanReturnsFalseForLesserValue()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(0);

        $this->assertSame(
            false,
            $subject->isGreaterThan(new Dimension\ContentDimensionValueSpecializationDepth(1))
        );
    }

    /**
     * @test
     */
    public function isGreaterThanReturnsFalseForEqualValue()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(0);

        $this->assertSame(
            false,
            $subject->isGreaterThan(new Dimension\ContentDimensionValueSpecializationDepth(0))
        );
    }

    /**
     * @test
     */
    public function isZeroReturnsTrueForDepthZero()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(0);

        $this->assertSame(
            true,
            $subject->isZero()
        );
    }

    /**
     * @test
     */
    public function isZeroReturnsFalseForDepthGreaterZero()
    {
        $subject = new Dimension\ContentDimensionValueSpecializationDepth(random_int(1, PHP_INT_MAX));

        $this->assertSame(
            false,
            $subject->isZero()
        );
    }
}
