<?php
namespace TYPO3\Media\Tests\Unit\Domain\Model\Adjustment;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Imagine\Image\Box;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use TYPO3\Media\Domain\Model\ImageInterface;

/**
 * Test case for the Resize Image Adjustment
 */
class ResizeImageAdjustmentTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function widthAndHeightDeterminedByExplicitlySetWidthAndHeightWithInsetMode() {
		/** @var ResizeImageAdjustment $adjustment */
		$adjustment = $this->getAccessibleMock('TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment', array('dummy'));

		$originalDimensions = new Box(400, 300);
		$expectedDimensions = new Box(110, 83);
		// The fallback mode is inset, so we do not set any mode here

		$adjustment->setWidth(110);
		$adjustment->setHeight(110);

		$this->assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
	}


	/**
	 * @test
	 */
	public function widthAndHeightDeterminedByExplicitlySetWidthAndHeightWithOutboundMode() {
		/** @var ResizeImageAdjustment $adjustment */
		$adjustment = $this->getAccessibleMock('TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment', array('dummy'));

		$originalDimensions = new Box(400, 300);
		$expectedDimensions = new Box(110, 110);

		$adjustment->setWidth(110);
		$adjustment->setHeight(110);
		$adjustment->setRatioMode(ImageInterface::RATIOMODE_OUTBOUND);

		$this->assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
	}

	/**
	 * @test
	 */
	public function ifWidthIsSetHeightIsDeterminedByTheOriginalAspectRatio() {
		/** @var ResizeImageAdjustment $adjustment */
		$adjustment = $this->getAccessibleMock('TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment', array('dummy'));

		$originalDimensions = new Box(400, 300);
		$expectedDimensions = new Box(110, 83);

		$adjustment->setWidth(110);

		$this->assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
	}

	/**
	 * @test
	 */
	public function ifHeightIsSetWidthIsDeterminedByTheOriginalAspectRatio() {
		/** @var ResizeImageAdjustment $adjustment */
		$adjustment = $this->getAccessibleMock('TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment', array('dummy'));

		$originalDimensions = new Box(400, 300);
		$expectedDimensions = new Box(127, 95);

		$adjustment->setHeight(95);

		$this->assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
	}

	/**
	 * Data provider for the test below
	 *
	 * @return array
	 */
	public function minimumAndMaximumDimensions() {
		return array(
			array(NULL, 110, NULL, NULL, 110, 83, ImageInterface::RATIOMODE_INSET, FALSE), # maximum width respects aspect ratio
			array(NULL, 110, NULL, 80, 106, 80, ImageInterface::RATIOMODE_INSET, FALSE),   # maximum height wins and aspect ratio is considered
			array(NULL, 110, NULL, 80, 106, 80, ImageInterface::RATIOMODE_INSET, TRUE),   # maximum height wins and aspect ratio is considered
			array(NULL, 110, NULL, NULL, 110, 83, ImageInterface::RATIOMODE_OUTBOUND, FALSE), # maximum width respects aspect ratio
			array(NULL, 110, NULL, 80, 106, 80, ImageInterface::RATIOMODE_OUTBOUND, FALSE),   # maximum height wins and aspect ratio is considered
			array(NULL, 110, NULL, 80, 106, 80, ImageInterface::RATIOMODE_OUTBOUND, TRUE),   # maximum height wins and aspect ratio is considered
			array(500, NULL, NULL, 310, 400, 300, ImageInterface::RATIOMODE_INSET, FALSE),   # upscaling not allowed, original image size wins
			array(500, NULL, NULL, 310, 413, 310, ImageInterface::RATIOMODE_INSET, TRUE),   # upscaling allowed, maximum height wins
			array(500, NULL, 500, NULL, 300, 300, ImageInterface::RATIOMODE_OUTBOUND, FALSE),   # upscaling not allowed, outbound box will be scaled down.
			array(500, NULL, 500, NULL, 500, 500, ImageInterface::RATIOMODE_OUTBOUND, TRUE),   # upscaling allowed, outbound box will be exact.
			array(500, 450, 500, 445, 445, 445, ImageInterface::RATIOMODE_OUTBOUND, TRUE),   # upscaling allowed, outbound box will be scaled to maximum sizes.
		);
	}

	/**
	 * @dataProvider minimumAndMaximumDimensions()
	 * @test
	 */
	public function combinationsOfMaximumAndMinimumWidthAndHeightAreCalculatedCorrectly($width, $maximumWidth, $height, $maximumHeight, $expectedWidth, $expectedHeight, $ratioMode, $allowUpScaling) {
		$options = array(
			'width' => $width,
			'maximumWidth' => $maximumWidth,
			'height' => $height,
			'maximumHeight' => $maximumHeight,
			'ratioMode' => $ratioMode,
			'allowUpScaling' => $allowUpScaling
		);

		/** @var ResizeImageAdjustment $adjustment */
		$adjustment = $this->getAccessibleMock('TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment', array('dummy'), array($options));

		$originalDimensions = new Box(400, 300);
		$expectedDimensions = new Box($expectedWidth, $expectedHeight);

		$this->assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
	}

}
