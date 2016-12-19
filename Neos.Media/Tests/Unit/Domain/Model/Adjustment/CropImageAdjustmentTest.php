<?php
namespace Neos\Media\Tests\Unit\Domain\Model\Adjustment;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Imagine\Image\Box;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use Neos\Media\Domain\Model\ImageInterface;

/**
 * Test case for the Crop Image Adjustment
 */
class CropImageAdjustmentTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function imageCropRefitDataProvider()
    {
        return [
            [
                0, 0, 100, 100,
                1000, 1000,
                0, 0, 1000, 1000
            ],
            [
                10, 10, 100, 100,
                1000, 1000,
                0, 0, 1000, 1000
            ],
            [
                50, 40, 300, 400,
                3000, 4000,
                0, 0, 3000, 4000
            ],
            [
                0, 0, 300, 400,
                3000, 8000,
                0, 0, 3000, 4000
            ],
            [
                0, 0, 400, 300,
                8000, 3000,
                0, 0, 4000, 3000
            ],
            [
                0, 0, 300, 400,
                8000, 3000,
                0, 0, 2250, 3000
            ],
            [
                0, 0, 400, 300,
                3000, 8000,
                0, 0, 3000, 2250
            ]
        ];
    }

    /**
     * @test
     * @dataProvider imageCropRefitDataProvider
     */
    public function refitFitsCropPropertionWithinImageSizeConstraints($cropX, $cropY, $cropWidth, $cropHeight, $newImageWidth, $newImageHeight, $expectedX, $expectedY, $expectedWidth, $expectedHeight)
    {
        $mockImage = $this->getMockBuilder(\Neos\Media\Domain\Model\Image::class)->disableOriginalConstructor()->getMock();
        $mockImage->expects($this->any())->method('getWidth')->will($this->returnValue($newImageWidth));
        $mockImage->expects($this->any())->method('getHeight')->will($this->returnValue($newImageHeight));

        $mockCropImageAdjustment = $this->getAccessibleMock(\Neos\Media\Domain\Model\Adjustment\CropImageAdjustment::class, ['dummy'], [], '', false);
        $mockCropImageAdjustment->_set('x', $cropX);
        $mockCropImageAdjustment->_set('y', $cropY);
        $mockCropImageAdjustment->_set('width', $cropWidth);
        $mockCropImageAdjustment->_set('height', $cropHeight);

        $mockCropImageAdjustment->refit($mockImage);

        $this->assertEquals($expectedX, $mockCropImageAdjustment->getX());
        $this->assertEquals($expectedY, $mockCropImageAdjustment->getY());
        $this->assertEquals($expectedWidth, $mockCropImageAdjustment->getWidth());
        $this->assertEquals($expectedHeight, $mockCropImageAdjustment->getHeight());
    }
}
