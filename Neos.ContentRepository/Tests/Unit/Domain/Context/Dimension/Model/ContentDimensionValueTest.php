<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Context\Dimension\Model;

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
 * Test cases for content dimension values
 */
class ContentDimensionValueTest extends UnitTestCase
{
    /**
     * @test
     */
    public function valueWithoutGeneralizationHasDepthZero()
    {
        $value = new Dimension\Model\ContentDimensionValue('test');

        $this->assertSame(0, $value->getDepth());
    }

    /**
     * @test
     */
    public function valueWithGeneralizationHasDepthOneGreaterThanGeneralization()
    {
        $testDepth = random_int(0, 100);
        $generalization = new Dimension\Model\ContentDimensionValue('generalization');
        ObjectAccess::setProperty($generalization, 'depth', $testDepth, true);
        $value = new Dimension\Model\ContentDimensionValue('test', $generalization);

        $this->assertSame($testDepth + 1, $value->getDepth());
    }

    /**
     * @test
     */
    public function calculateFallbackDepthReturnsZeroRelativeToSelf()
    {
        $value = new Dimension\Model\ContentDimensionValue('generalization');

        $this->assertSame(0, $value->calculateFallbackDepth($value));
    }

    /**
     * @test
     */
    public function calculateFallbackDepthReturnsLevelOfAncestryForValidGeneralization()
    {
        $testLevel = random_int(1, 10);

        $rootGeneralization = new Dimension\Model\ContentDimensionValue('generalization-level0');
        $currentLevel = 1;
        $previousGeneralization = $rootGeneralization;
        $currentGeneralization = null;
        while ($currentLevel <= $testLevel) {
            $currentGeneralization = new Dimension\Model\ContentDimensionValue('generalization-level' . $currentLevel, $previousGeneralization);
            $currentLevel++;
            $previousGeneralization = $currentGeneralization;
        }

        $this->assertSame($testLevel, $currentGeneralization->calculateFallbackDepth($rootGeneralization));
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Domain\Context\Dimension\Exception\InvalidFallbackException
     */
    public function calculateFallbackDepthThrowsExceptionForDisconnectedValue()
    {
        $testValue = new Dimension\Model\ContentDimensionValue('test');
        $disconnectedValue = new Dimension\Model\ContentDimensionValue('test2');

        $testValue->calculateFallbackDepth($disconnectedValue);
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Domain\Context\Dimension\Exception\InvalidFallbackException
     */
    public function calculateFallbackDepthThrowsExceptionForSpecialization()
    {
        $value = new Dimension\Model\ContentDimensionValue('test');
        $specialization = new Dimension\Model\ContentDimensionValue('specialization', $value);

        $value->calculateFallbackDepth($specialization);
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Domain\Context\Dimension\Exception\InvalidFallbackException
     */
    public function calculateFallbackDepthThrowsExceptionForConnectedButUnreachableValue()
    {
        $value = new Dimension\Model\ContentDimensionValue('value');
        $specialization1 = new Dimension\Model\ContentDimensionValue('specialization1', $value);
        $specialization2 = new Dimension\Model\ContentDimensionValue('specialization2', $value);

        $specialization1->calculateFallbackDepth($specialization2);
    }
}
