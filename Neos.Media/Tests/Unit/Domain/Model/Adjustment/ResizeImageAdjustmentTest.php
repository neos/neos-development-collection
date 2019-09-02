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

use Neos\Media\Imagine\Box;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use Neos\Media\Domain\Model\ImageInterface;

/**
 * Test case for the Resize Image Adjustment
 */
class ResizeImageAdjustmentTest extends UnitTestCase
{
    /**
     * @test
     */
    public function widthAndHeightDeterminedByExplicitlySetWidthAndHeightWithInsetMode()
    {
        /** @var ResizeImageAdjustment $adjustment */
        $adjustment = $this->getAccessibleMock(ResizeImageAdjustment::class, ['dummy']);

        $originalDimensions = new Box(400, 300);
        $expectedDimensions = new Box(110, 83);
        // The fallback mode is inset, so we do not set any mode here

        $adjustment->setWidth(110);
        $adjustment->setHeight(110);

        self::assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
    }


    /**
     * @test
     */
    public function widthAndHeightDeterminedByExplicitlySetWidthAndHeightWithOutboundMode()
    {
        /** @var ResizeImageAdjustment $adjustment */
        $adjustment = $this->getAccessibleMock(ResizeImageAdjustment::class, ['dummy']);

        $originalDimensions = new Box(400, 300);
        $expectedDimensions = new Box(110, 110);

        $adjustment->setWidth(110);
        $adjustment->setHeight(110);
        $adjustment->setRatioMode(ImageInterface::RATIOMODE_OUTBOUND);

        self::assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
    }

    /**
     * @test
     */
    public function ifWidthIsSetHeightIsDeterminedByTheOriginalAspectRatio()
    {
        /** @var ResizeImageAdjustment $adjustment */
        $adjustment = $this->getAccessibleMock(ResizeImageAdjustment::class, ['dummy']);

        $originalDimensions = new Box(400, 300);
        $expectedDimensions = new Box(110, 83);

        $adjustment->setWidth(110);

        self::assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
    }

    /**
     * @test
     */
    public function ifHeightIsSetWidthIsDeterminedByTheOriginalAspectRatio()
    {
        /** @var ResizeImageAdjustment $adjustment */
        $adjustment = $this->getAccessibleMock(ResizeImageAdjustment::class, ['dummy']);

        $originalDimensions = new Box(400, 300);
        $expectedDimensions = new Box(127, 95);

        $adjustment->setHeight(95);

        self::assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
    }

    /**
     * @test
     */
    public function minimumHeightIsGreaterZero()
    {
        /** @var ResizeImageAdjustment $adjustment */
        $adjustment = $this->getAccessibleMock(ResizeImageAdjustment::class, ['dummy'], [
            [
                'maximumWidth' => 250,
                'maximumHeight' => 250,
                'ratioMode' => ImageInterface::RATIOMODE_INSET
            ]
        ]);

        $originalDimensions = new Box(2000, 2);
        $expectedDimensions = new Box(250, 1);

        self::assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
    }

    /**
     * @test
     */
    public function minimumWidthIsGreaterZero()
    {
        /** @var ResizeImageAdjustment $adjustment */
        $adjustment = $this->getAccessibleMock(ResizeImageAdjustment::class, ['dummy'], [
            [
                'maximumWidth' => 250,
                'maximumHeight' => 250,
                'ratioMode' => ImageInterface::RATIOMODE_INSET
            ]
        ]);

        $originalDimensions = new Box(2, 2000);
        $expectedDimensions = new Box(1, 250);

        self::assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
    }

    /**
     * Data provider for the test below
     *
     * @return array
     */
    public function minimumAndMaximumDimensions()
    {
        return [
            [null, 110, null, null, 110, 83, ImageInterface::RATIOMODE_INSET, false], # maximum width respects aspect ratio
            [null, 110, null, 80, 106, 80, ImageInterface::RATIOMODE_INSET, false],   # maximum height wins and aspect ratio is considered
            [null, 110, null, 80, 106, 80, ImageInterface::RATIOMODE_INSET, true],   # maximum height wins and aspect ratio is considered
            [null, 110, null, null, 110, 83, ImageInterface::RATIOMODE_OUTBOUND, false], # maximum width respects aspect ratio
            [null, 110, null, 80, 106, 80, ImageInterface::RATIOMODE_OUTBOUND, false],   # maximum height wins and aspect ratio is considered
            [null, 110, null, 80, 106, 80, ImageInterface::RATIOMODE_OUTBOUND, true],   # maximum height wins and aspect ratio is considered
            [500, null, null, 310, 400, 300, ImageInterface::RATIOMODE_INSET, false],   # upscaling not allowed, original image size wins
            [500, null, null, 310, 413, 310, ImageInterface::RATIOMODE_INSET, true],   # upscaling allowed, maximum height wins
            [500, null, 500, null, 300, 300, ImageInterface::RATIOMODE_OUTBOUND, false],   # upscaling not allowed, outbound box will be scaled down.
            [500, null, 500, null, 500, 500, ImageInterface::RATIOMODE_OUTBOUND, true],   # upscaling allowed, outbound box will be exact.
            [500, 450, 500, 445, 445, 445, ImageInterface::RATIOMODE_OUTBOUND, true],   # upscaling allowed, outbound box will be scaled to maximum sizes.
        ];
    }

    /**
     * @dataProvider minimumAndMaximumDimensions()
     * @test
     */
    public function combinationsOfMaximumAndMinimumWidthAndHeightAreCalculatedCorrectly($width, $maximumWidth, $height, $maximumHeight, $expectedWidth, $expectedHeight, $ratioMode, $allowUpScaling)
    {
        $options = [
            'width' => $width,
            'maximumWidth' => $maximumWidth,
            'height' => $height,
            'maximumHeight' => $maximumHeight,
            'ratioMode' => $ratioMode,
            'allowUpScaling' => $allowUpScaling
        ];

        /** @var ResizeImageAdjustment $adjustment */
        $adjustment = $this->getAccessibleMock(ResizeImageAdjustment::class, ['dummy'], [$options]);

        $originalDimensions = new Box(400, 300);
        $expectedDimensions = new Box($expectedWidth, $expectedHeight);

        self::assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
    }
}
