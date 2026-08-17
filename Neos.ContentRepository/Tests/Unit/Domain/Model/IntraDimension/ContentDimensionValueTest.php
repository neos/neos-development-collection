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
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Domain\Model\IntraDimension\ContentDimensionValue;
use Neos\ContentRepository\Domain\Model\IntraDimension\Exception\InvalidFallbackException;
use Neos\ContentRepository\Domain\Model\IntraDimension;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;

/**
 * Test cases for content dimension values
 */
class ContentDimensionValueTest extends UnitTestCase
{
    #[Test]
    public function valueWithoutFallbackHasDepthZero()
    {
        $value = new ContentDimensionValue('test');

        self::assertSame(0, $value->getDepth());
    }

    #[Test]
    public function valueWithFallbackHasDepthOneGreaterThanFallback()
    {
        $testDepth = random_int(0, 100);
        $fallbackValue = new ContentDimensionValue('fallback');
        ObjectAccess::setProperty($fallbackValue, 'depth', $testDepth, true);
        $value = new ContentDimensionValue('test', $fallbackValue);

        self::assertSame($testDepth + 1, $value->getDepth());
    }

    #[Test]
    public function calculateFallbackDepthReturnsZeroRelativeToSelf()
    {
        $value = new ContentDimensionValue('fallback');

        self::assertSame(0, $value->calculateFallbackDepth($value));
    }

    #[Test]
    public function calculateFallbackDepthReturnsLevelOfAncestryForValidFallback()
    {
        $testLevel = random_int(1, 10);

        $rootFallback = new ContentDimensionValue('fallback-level0');
        $currentLevel = 1;
        $previousFallback = $rootFallback;
        $currentFallback = null;
        while ($currentLevel <= $testLevel) {
            $currentFallback = new ContentDimensionValue('fallback-level' . $currentLevel, $previousFallback);
            $currentLevel++;
            $previousFallback = $currentFallback;
        }

        self::assertSame($testLevel, $currentFallback->calculateFallbackDepth($rootFallback));
    }

    #[Test]
    public function calculateFallbackDepthThrowsExceptionForDisconnectedValue()
    {
        $this->expectException(InvalidFallbackException::class);
        $testValue = new ContentDimensionValue('test');
        $disconnectedValue = new ContentDimensionValue('test2');

        $testValue->calculateFallbackDepth($disconnectedValue);
    }

    #[Test]
    public function calculateFallbackDepthThrowsExceptionForVariant()
    {
        $this->expectException(InvalidFallbackException::class);
        $fallback = new ContentDimensionValue('fallback');
        $variant = new ContentDimensionValue('variant', $fallback);

        $fallback->calculateFallbackDepth($variant);
    }

    #[Test]
    public function calculateFallbackDepthThrowsExceptionForConnectedButUnreachableValue()
    {
        $this->expectException(InvalidFallbackException::class);
        $fallback = new ContentDimensionValue('fallback');
        $variant1 = new ContentDimensionValue('variant1', $fallback);
        $variant2 = new ContentDimensionValue('variant2', $fallback);

        $variant1->calculateFallbackDepth($variant2);
    }
}
