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

/**
 * Test case for the Resize Image Adjustment
 */
class ResizeImageAdjustmentTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function widthAndHeightDeterminedByExplicitlySetWidthAndHeight() {
		/** @var ResizeImageAdjustment $adjustment */
		$adjustment = $this->getAccessibleMock('TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment', array('dummy'));

		$originalDimensions = new Box(400, 300);
		$expectedDimensions = new Box(110, 110);

		$adjustment->setWidth(110);
		$adjustment->setHeight(110);

		$this->assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
	}

	/**
	 * @test
	 */
	public function ifWidthIsSetHeightIsDeterminedByTheOriginalAspectRatio() {
		/** @var ResizeImageAdjustment $adjustment */
		$adjustment = $this->getAccessibleMock('TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment', array('dummy'));

		$originalDimensions = new Box(400, 300);
		$expectedDimensions = new Box(110, 82);

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
		$expectedDimensions = new Box(126, 95);

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
			array(NULL, 110, NULL, NULL, 110, 82),	// maximum width respects aspect ratio
			array(NULL, 110, NULL, 80, 106, 80),	// maximum height wins
		);
	}

	/**
	 * @dataProvider minimumAndMaximumDimensions()
	 * @test
	 */
	public function combinationsOfMaximumAndMinimumWidthAndHeightAreCalculatedCorrectly($minimumWidth, $maximumWidth, $minimumHeight, $maximumHeight, $expectedWidth, $expectedHeight) {
		$options = array(
			'minimumWidth' => $minimumWidth,
			'maximumWidth' => $maximumWidth,
			'minimumHeight' => $minimumHeight,
			'maximumHeight' => $maximumHeight
		);

		/** @var ResizeImageAdjustment $adjustment */
		$adjustment = $this->getAccessibleMock('TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment', array('dummy'), array($options));

		$originalDimensions = new Box(400, 300);
		$expectedDimensions = new Box($expectedWidth, $expectedHeight);

		$this->assertEquals($expectedDimensions, $adjustment->_call('calculateDimensions', $originalDimensions));
	}

}
