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
 * Test cases for content dimension values
 */
class ContentDimensionValueTest extends UnitTestCase
{
    /**
     * @test
     */
    public function valueWithoutFallbackHasDepthZero()
    {
        $value = new IntraDimension\ContentDimensionValue('test');

        self::assertSame(0, $value->getDepth());
    }

    /**
     * @test
     */
    public function valueWithFallbackHasDepthOneGreaterThanFallback()
    {
        $testDepth = random_int(0, 100);
        $fallbackValue = new IntraDimension\ContentDimensionValue('fallback');
        ObjectAccess::setProperty($fallbackValue, 'depth', $testDepth, true);
        $value = new IntraDimension\ContentDimensionValue('test', $fallbackValue);

        self::assertSame($testDepth + 1, $value->getDepth());
    }

    /**
     * @test
     */
    public function calculateFallbackDepthReturnsZeroRelativeToSelf()
    {
        $value = new IntraDimension\ContentDimensionValue('fallback');

        self::assertSame(0, $value->calculateFallbackDepth($value));
    }

    /**
     * @test
     */
    public function calculateFallbackDepthReturnsLevelOfAncestryForValidFallback()
    {
        $testLevel = random_int(1, 10);

        $rootFallback = new IntraDimension\ContentDimensionValue('fallback-level0');
        $currentLevel = 1;
        $previousFallback = $rootFallback;
        $currentFallback = null;
        while ($currentLevel <= $testLevel) {
            $currentFallback = new IntraDimension\ContentDimensionValue('fallback-level' . $currentLevel, $previousFallback);
            $currentLevel++;
            $previousFallback = $currentFallback;
        }

        self::assertSame($testLevel, $currentFallback->calculateFallbackDepth($rootFallback));
    }

    /**
     * @test
     */
    public function calculateFallbackDepthThrowsExceptionForDisconnectedValue()
    {
        $this->expectException(IntraDimension\Exception\InvalidFallbackException::class);
        $testValue = new IntraDimension\ContentDimensionValue('test');
        $disconnectedValue = new IntraDimension\ContentDimensionValue('test2');

        $testValue->calculateFallbackDepth($disconnectedValue);
    }

    /**
     * @test
     */
    public function calculateFallbackDepthThrowsExceptionForVariant()
    {
        $this->expectException(IntraDimension\Exception\InvalidFallbackException::class);
        $fallback = new IntraDimension\ContentDimensionValue('fallback');
        $variant = new IntraDimension\ContentDimensionValue('variant', $fallback);

        $fallback->calculateFallbackDepth($variant);
    }

    /**
     * @test
     */
    public function calculateFallbackDepthThrowsExceptionForConnectedButUnreachableValue()
    {
        $this->expectException(IntraDimension\Exception\InvalidFallbackException::class);
        $fallback = new IntraDimension\ContentDimensionValue('fallback');
        $variant1 = new IntraDimension\ContentDimensionValue('variant1', $fallback);
        $variant2 = new IntraDimension\ContentDimensionValue('variant2', $fallback);

        $variant1->calculateFallbackDepth($variant2);
    }
}
