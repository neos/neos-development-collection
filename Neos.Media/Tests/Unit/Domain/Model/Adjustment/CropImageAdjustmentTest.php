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
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\Color\RGB as RGBColor;
use Imagine\Image\Palette\RGB as RGBPalette;
use Imagine\Image\Point;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\ValueObject\Configuration\AspectRatio;

/**
 * Test case for the Crop Image Adjustment
 */
class CropImageAdjustmentTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('gd')) {
            self::markTestSkipped('ext-gd is not available, skipping test');
        }
    }

    /**
     * @test
     */
    public function aspectRatioCanBeSetInsteadOfAbsoluteDimensions(): void
    {
        $imagine = new Imagine();
        $size = new Box(1600, 1000);
        $image = $imagine->create($size);

        $cropImageAdjustment = new CropImageAdjustment();
        $cropImageAdjustment->setAspectRatio(AspectRatio::fromString('16:9'));

        self::assertTrue($cropImageAdjustment->canBeApplied($image));
    }

    /**
     * @test
     */
    public function settingAnAspectRatioRemovesValuesForManualDimensions(): void
    {
        $cropImageAdjustment = new CropImageAdjustment();
        $cropImageAdjustment->setX(10);
        $cropImageAdjustment->setY(20);
        $cropImageAdjustment->setWidth(100);
        $cropImageAdjustment->setHeight(100);

        $cropImageAdjustment->setAspectRatio(AspectRatio::fromString('16:9'));

        self::assertNull($cropImageAdjustment->getX());
        self::assertNull($cropImageAdjustment->getY());
        self::assertNull($cropImageAdjustment->getWidth());
        self::assertNull($cropImageAdjustment->getHeight());

        self::assertSame((string)$cropImageAdjustment->getAspectRatio(), '16:9');
    }

    /**
     * @test
     */
    public function settingManualDimensionsRemovesAspectRatio(): void
    {
        $cropImageAdjustment = new CropImageAdjustment();

        $cropImageAdjustment->setAspectRatio(AspectRatio::fromString('16:9'));
        $cropImageAdjustment->setX(10);
        self::assertNull($cropImageAdjustment->getAspectRatio());

        $cropImageAdjustment->setAspectRatio(AspectRatio::fromString('16:9'));
        $cropImageAdjustment->setY(20);
        self::assertNull($cropImageAdjustment->getAspectRatio());

        $cropImageAdjustment->setAspectRatio(AspectRatio::fromString('16:9'));
        $cropImageAdjustment->setWidth(100);
        self::assertNull($cropImageAdjustment->getAspectRatio());

        $cropImageAdjustment->setAspectRatio(AspectRatio::fromString('16:9'));
        $cropImageAdjustment->setHeight(100);
        self::assertNull($cropImageAdjustment->getAspectRatio());
    }

    /**
     * @test
     */
    public function canBeAppliedReturnsFalseIfAspectRatioEqualsOriginalAspectRatio(): void
    {
        $imagine = new Imagine();
        $size = new Box(1600, 900);
        $image = $imagine->create($size);

        $cropImageAdjustment = new CropImageAdjustment();
        $cropImageAdjustment->setAspectRatio(AspectRatio::fromString('16:9'));

        self::assertFalse($cropImageAdjustment->canBeApplied($image));
    }

    /**
     * @return array
     */
    public function imageCropByAspectRatioDataProvider(): array
    {
        return [
            ['16:9', 1600, 1000, 0, 50, 1600, 900],
            ['16:9', 1000, 1000, 0, 219, 1000, 563],
            ['4:3', 1000, 1000, 0, 125, 1000, 750]
        ];
    }

    /**
     * @test
     * @dataProvider imageCropByAspectRatioDataProvider
     * @param string $aspectRatio
     * @param int $originalWidth
     * @param int $originalHeight
     * @param int $expectedX
     * @param int $expectedY
     * @param int $expectedWidth
     * @param int $expectedHeight
     */
    public function aspectRatioIsAppliedWithMaximumPossibleClipping(string $aspectRatio, int $originalWidth, int $originalHeight, int $expectedX, int $expectedY, int $expectedWidth, int $expectedHeight): void
    {
        $imagine = new Imagine();
        $size = new Box($originalWidth, $originalHeight);
        $image = $imagine->create($size);

        $color = new RGBColor(new RGBPalette(), [100, 100, 100], 100);
        $point = new Point($expectedX, $expectedY);
        $image->draw()->dot($point, $color);

        $cropImageAdjustment = new CropImageAdjustment();
        $cropImageAdjustment->setAspectRatio(AspectRatio::fromString($aspectRatio));

        $cropImageAdjustment->applyToImage($image);

        $imageSize = $image->getSize();
        self::assertSame($expectedWidth, $imageSize->getWidth());
        self::assertSame($expectedHeight, $imageSize->getHeight());
        self::assertSame(100, $image->getColorAt(new Point(0, 0))->getValue(ColorInterface::COLOR_RED));
    }

    /**
     * @test
     */
    public function canBeAppliedReturnsTrueIfCropClippingIsSmallerThanTheImage(): void
    {
        $imagine = new Imagine();
        $size = new Box(1600, 900);
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
        $size = new Box(1600, 900);
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
                0,
                0,
                100,
                100,
                1000,
                1000,
                0,
                0,
                1000,
                1000
            ],
            [
                10,
                10,
                100,
                100,
                1000,
                1000,
                0,
                0,
                1000,
                1000
            ],
            [
                50,
                40,
                300,
                400,
                3000,
                4000,
                0,
                0,
                3000,
                4000
            ],
            [
                0,
                0,
                300,
                400,
                3000,
                8000,
                0,
                0,
                3000,
                4000
            ],
            [
                0,
                0,
                400,
                300,
                8000,
                3000,
                0,
                0,
                4000,
                3000
            ],
            [
                0,
                0,
                300,
                400,
                8000,
                3000,
                0,
                0,
                2250,
                3000
            ],
            [
                0,
                0,
                400,
                300,
                3000,
                8000,
                0,
                0,
                3000,
                2250
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
        $size = new Box($newImageWidth, $newImageHeight);
        $image = $imagine->create($size);

        $cropImageAdjustment = new CropImageAdjustment();
        $cropImageAdjustment->setX($cropX);
        $cropImageAdjustment->setY($cropY);
        $cropImageAdjustment->setWidth($cropWidth);
        $cropImageAdjustment->setHeight($cropHeight);

        $cropImageAdjustment->refit($image);

        self::assertEquals($expectedX, $cropImageAdjustment->getX());
        self::assertEquals($expectedY, $cropImageAdjustment->getY());
        self::assertEquals($expectedWidth, $cropImageAdjustment->getWidth());
        self::assertEquals($expectedHeight, $cropImageAdjustment->getHeight());
    }
}
