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

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;

/**
 * Test case for the Crop Image Adjustment
 */
class CropImageAdjustmentTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canBeAppliedReturnsTrueIfCropClippingIsSmallerThanTheImage(): void
    {
        $imagine = new Imagine();
        $size  = new Box(1600, 900);
        $image = $imagine->create($size);

        $cropImageAdjustment = new CropImageAdjustment();
        $cropImageAdjustment->setX(100);
        $cropImageAdjustment->setY(100);
        $cropImageAdjustment->setWidth(900);
        $cropImageAdjustment->setHeight(400);

        self::assertTrue($cropImageAdjustment->canBeApplied($image));
    }

    /**
     * @test
     */
    public function canBeAppliedReturnsFalseIfCropClippingIsTheFullImage(): void
    {
        $imagine = new Imagine();
        $size  = new Box(1600, 900);
        $image = $imagine->create($size);

        $cropImageAdjustment = new CropImageAdjustment();
        $cropImageAdjustment->setX(0);
        $cropImageAdjustment->setY(0);
        $cropImageAdjustment->setWidth(1600);
        $cropImageAdjustment->setHeight(900);

        self::assertFalse($cropImageAdjustment->canBeApplied($image));
    }

    /**
     * @return array
     */
    public function imageCropRefitDataProvider(): array
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
     * @param int $cropX
     * @param int $cropY
     * @param int $cropWidth
     * @param int $cropHeight
     * @param int $newImageWidth
     * @param int $newImageHeight
     * @param int $expectedX
     * @param int $expectedY
     * @param int $expectedWidth
     * @param int $expectedHeight
     */
    public function refitFitsCropPropertyWithinImageSizeConstraints(int $cropX, int $cropY, int $cropWidth, int $cropHeight, int $newImageWidth, int $newImageHeight, int $expectedX, int $expectedY, int $expectedWidth, int $expectedHeight): void
    {
        $imagine = new Imagine();
        $size  = new Box($newImageWidth, $newImageHeight);
        $image = $imagine->create($size);

        $cropImageAdjustment = new CropImageAdjustment();
        $cropImageAdjustment->setX($cropX);
        $cropImageAdjustment->setY($cropY);
        $cropImageAdjustment->setWidth($cropWidth);
        $cropImageAdjustment->setHeight($cropHeight);

        $cropImageAdjustment->refit($image);

        $this->assertEquals($expectedX, $cropImageAdjustment->getX());
        $this->assertEquals($expectedY, $cropImageAdjustment->getY());
        $this->assertEquals($expectedWidth, $cropImageAdjustment->getWidth());
        $this->assertEquals($expectedHeight, $cropImageAdjustment->getHeight());
    }
}
