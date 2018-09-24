<?php
namespace Neos\EventSourcedContentRepository\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain;
use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for the Dimension Space Points
 */
class DimensionSpacePointTest extends UnitTestCase
{
    /**
     * @test
     */
    public function varyCreatesCorrectNewDimensionSpacePoint()
    {
        $dimensionSpacePoint = new Domain\ValueObject\DimensionSpacePoint(['dimensionA' => 'value1', 'dimensionB' => 'value1']);

        $this->assertEquals(
            new Domain\ValueObject\DimensionSpacePoint(['dimensionA' => 'value2', 'dimensionB' => 'value1']),
            $dimensionSpacePoint->vary(new Dimension\ContentDimensionIdentifier('dimensionA'), 'value2')
        );
    }

    /**
     * @test
     */
    public function isDirectVariantInDimensionCorrectlyEvaluatesCoordinates()
    {
        $dimensionSpacePointA = new Domain\ValueObject\DimensionSpacePoint(['dimensionA' => 'value1', 'dimensionB' => 'value1']);
        $dimensionSpacePointB = new Domain\ValueObject\DimensionSpacePoint(['dimensionA' => 'value2', 'dimensionB' => 'value1']);
        $dimensionSpacePointC = new Domain\ValueObject\DimensionSpacePoint(['dimensionA' => 'value2', 'dimensionB' => 'value2']);

        $this->assertSame(false, $dimensionSpacePointA->isDirectVariantInDimension($dimensionSpacePointA, new Dimension\ContentDimensionIdentifier('dimensionA')));
        $this->assertSame(false, $dimensionSpacePointA->isDirectVariantInDimension($dimensionSpacePointA, new Dimension\ContentDimensionIdentifier('dimensionB')));
        $this->assertSame(true, $dimensionSpacePointA->isDirectVariantInDimension($dimensionSpacePointB, new Dimension\ContentDimensionIdentifier('dimensionA')));
        $this->assertSame(false, $dimensionSpacePointA->isDirectVariantInDimension($dimensionSpacePointB, new Dimension\ContentDimensionIdentifier('dimensionB')));
        $this->assertSame(false, $dimensionSpacePointA->isDirectVariantInDimension($dimensionSpacePointC, new Dimension\ContentDimensionIdentifier('dimensionA')));
        $this->assertSame(false, $dimensionSpacePointA->isDirectVariantInDimension($dimensionSpacePointC, new Dimension\ContentDimensionIdentifier('dimensionB')));

        $this->assertSame(true, $dimensionSpacePointB->isDirectVariantInDimension($dimensionSpacePointA, new Dimension\ContentDimensionIdentifier('dimensionA')));
        $this->assertSame(false, $dimensionSpacePointB->isDirectVariantInDimension($dimensionSpacePointA, new Dimension\ContentDimensionIdentifier('dimensionB')));
        $this->assertSame(false, $dimensionSpacePointB->isDirectVariantInDimension($dimensionSpacePointB, new Dimension\ContentDimensionIdentifier('dimensionA')));
        $this->assertSame(false, $dimensionSpacePointB->isDirectVariantInDimension($dimensionSpacePointB, new Dimension\ContentDimensionIdentifier('dimensionB')));
        $this->assertSame(false, $dimensionSpacePointB->isDirectVariantInDimension($dimensionSpacePointC, new Dimension\ContentDimensionIdentifier('dimensionA')));
        $this->assertSame(true, $dimensionSpacePointB->isDirectVariantInDimension($dimensionSpacePointC, new Dimension\ContentDimensionIdentifier('dimensionB')));

        $this->assertSame(false, $dimensionSpacePointC->isDirectVariantInDimension($dimensionSpacePointA, new Dimension\ContentDimensionIdentifier('dimensionA')));
        $this->assertSame(false, $dimensionSpacePointC->isDirectVariantInDimension($dimensionSpacePointA, new Dimension\ContentDimensionIdentifier('dimensionB')));
        $this->assertSame(false, $dimensionSpacePointC->isDirectVariantInDimension($dimensionSpacePointB, new Dimension\ContentDimensionIdentifier('dimensionA')));
        $this->assertSame(true, $dimensionSpacePointC->isDirectVariantInDimension($dimensionSpacePointB, new Dimension\ContentDimensionIdentifier('dimensionB')));
        $this->assertSame(false, $dimensionSpacePointC->isDirectVariantInDimension($dimensionSpacePointC, new Dimension\ContentDimensionIdentifier('dimensionA')));
        $this->assertSame(false, $dimensionSpacePointC->isDirectVariantInDimension($dimensionSpacePointC, new Dimension\ContentDimensionIdentifier('dimensionB')));
    }
}
