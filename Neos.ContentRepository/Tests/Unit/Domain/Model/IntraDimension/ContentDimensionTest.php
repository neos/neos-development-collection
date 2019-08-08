<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model\IntraDimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\IntraDimension;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;

/**
 * Test cases for content dimensions
 */
class ContentDimensionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createValueRegistersCreatedValue()
    {
        $dimension = new IntraDimension\ContentDimension('test');
        $testValue = $dimension->createValue('test');

        self::assertSame($testValue, $dimension->getValue('test'));
    }

    /**
     * @test
     */
    public function createValueWithoutFallbackDoesNotIncreaseDepth()
    {
        $dimension = new IntraDimension\ContentDimension('test');
        $dimension->createValue('test');

        self::assertSame(0, $dimension->getDepth());
    }

    /**
     * @test
     */
    public function createValueWithFallbackDoesNotDecreaseDepth()
    {
        $testDepth = random_int(1, 100);
        $dimension = new IntraDimension\ContentDimension('test');
        ObjectAccess::setProperty($dimension, 'depth', $testDepth, true);
        $fallbackValue = $dimension->createValue('fallback');
        $dimension->createValue('test', $fallbackValue);

        self::assertSame($testDepth, $dimension->getDepth());
    }

    /**
     * @test
     */
    public function createValueWithFallbackIncreasesDepthIfFallbackHasCurrentMaximumDepth()
    {
        $testDepth = random_int(0, 100);
        $dimension = new IntraDimension\ContentDimension('test');
        ObjectAccess::setProperty($dimension, 'depth', $testDepth, true);
        $fallbackValue = $dimension->createValue('fallback');
        ObjectAccess::setProperty($fallbackValue, 'depth', $testDepth, true);
        $dimension->createValue('test', $fallbackValue);

        self::assertSame($testDepth + 1, $dimension->getDepth());
    }

    /**
     * @test
     */
    public function getRootValuesOnlyReturnsValuesOfDepthZero()
    {
        $testDepth = random_int(1, 100);
        $dimension = new IntraDimension\ContentDimension('test');
        $depthZeroValue = $dimension->createValue('depthZero');
        $depthGreaterZeroValue = $dimension->createValue('depthGreaterZero');
        ObjectAccess::setProperty($depthGreaterZeroValue, 'depth', $testDepth, true);

        self::assertContains($depthZeroValue, $dimension->getRootValues());
        self::assertNotContains($depthGreaterZeroValue, $dimension->getRootValues());
    }
}
