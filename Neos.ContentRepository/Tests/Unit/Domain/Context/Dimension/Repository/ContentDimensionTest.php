<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Context\Dimension\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\Dimension;
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
        $dimension = new Dimension\Repository\ContentDimension('test');
        $testValue = $dimension->createValue('test');

        $this->assertSame($testValue, $dimension->getValue('test'));
    }

    /**
     * @test
     */
    public function createValueWithoutGeneralizationDoesNotIncreaseDepth()
    {
        $dimension = new Dimension\Repository\ContentDimension('test');
        $dimension->createValue('test');

        $this->assertSame(0, $dimension->getDepth());
    }

    /**
     * @test
     */
    public function createValueWithGeneralizationDoesNotDecreaseDepth()
    {
        $testDepth = random_int(1, 100);
        $dimension = new Dimension\Repository\ContentDimension('test');
        ObjectAccess::setProperty($dimension, 'depth', $testDepth, true);
        $generalization = $dimension->createValue('generalization');
        $dimension->createValue('test', $generalization);

        $this->assertSame($testDepth, $dimension->getDepth());
    }

    /**
     * @test
     */
    public function createValueWithGeneralizationIncreasesDepthIfGeneralizationHasCurrentMaximumDepth()
    {
        $testDepth = random_int(0, 100);
        $dimension = new Dimension\Repository\ContentDimension('test');
        ObjectAccess::setProperty($dimension, 'depth', $testDepth, true);
        $generalization = $dimension->createValue('generalization');
        ObjectAccess::setProperty($generalization, 'depth', $testDepth, true);
        $dimension->createValue('test', $generalization);

        $this->assertSame($testDepth + 1, $dimension->getDepth());
    }

    /**
     * @test
     */
    public function getRootValuesOnlyReturnsValuesOfDepthZero()
    {
        $testDepth = random_int(1, 100);
        $dimension = new Dimension\Repository\ContentDimension('test');
        $depthZeroValue = $dimension->createValue('depthZero');
        $depthGreaterZeroValue = $dimension->createValue('depthGreaterZero');
        ObjectAccess::setProperty($depthGreaterZeroValue, 'depth', $testDepth, true);

        $this->assertContains($depthZeroValue, $dimension->getRootValues());
        $this->assertNotContains($depthGreaterZeroValue, $dimension->getRootValues());
    }
}
