<?php
namespace TYPO3\Media\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the image ViewHelper
 */
class ImageViewHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Media\ViewHelpers\ImageViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var \TYPO3\Fluid\Core\ViewHelper\TagBuilder
	 */
	protected $mockTagBuilder;

	/**
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 */
	protected $mockResourcePublisher;

	/**
	 * @var \TYPO3\Media\Domain\Model\Image
	 */
	protected $mockImage;

	/**
	 * @var \TYPO3\Media\Domain\Model\ImageVariant
	 */
	protected $mockThumbnail;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->viewHelper = $this->getAccessibleMock('TYPO3\Media\ViewHelpers\ImageViewHelper', array('dummy'));
		$this->mockTagBuilder = $this->getMock('TYPO3\Fluid\Core\ViewHelper\TagBuilder');
		$this->viewHelper->injectTagBuilder($this->mockTagBuilder);
		$this->mockResourcePublisher = $this->getMock('TYPO3\Flow\Resource\Publishing\ResourcePublisher');
		$this->viewHelper->_set('resourcePublisher', $this->mockResourcePublisher);
		$this->mockImage = $this->getMock('TYPO3\Media\Domain\Model\ImageInterface');
		$this->mockImage->expects($this->any())->method('getWidth')->will($this->returnValue(100));
		$this->mockImage->expects($this->any())->method('getHeight')->will($this->returnValue(100));
		$this->mockThumbnail = $this->getMockBuilder('TYPO3\Media\Domain\Model\ImageVariant')->disableOriginalConstructor()->getMock();
	}

	/**
	 * @test
	 */
	public function ratioModeDefaultsToInset() {
		$this->mockImage->expects($this->once())->method('getThumbnail')->with(50, 100, \TYPO3\Media\Domain\Model\Image::RATIOMODE_INSET)->will($this->returnValue($this->mockThumbnail));
		$this->viewHelper->render($this->mockImage, 50);
	}

	/**
	 * @test
	 */
	public function ratioModeIsOutboundIfAllowCroppingIsTrue() {
		$this->mockImage->expects($this->once())->method('getThumbnail')->with(50, 100, \TYPO3\Media\Domain\Model\Image::RATIOMODE_OUTBOUND)->will($this->returnValue($this->mockThumbnail));
		$this->viewHelper->render($this->mockImage, 50, NULL, TRUE);
	}

	/**
	 * @test
	 */
	public function thumbnailWidthDoesNotExceedImageWithByDefault() {
		$this->mockImage->expects($this->never())->method('getThumbnail');
		$this->viewHelper->render($this->mockImage, 456, NULL);
	}

	/**
	 * @test
	 */
	public function thumbnailHeightDoesNotExceedImageHeightByDefault() {
		$this->mockImage->expects($this->never())->method('getThumbnail');
		$this->viewHelper->render($this->mockImage, NULL, 123);
	}

	/**
	 * @test
	 */
	public function thumbnailWidthMightExceedImageWithIfAllowUpScalingIsTrue() {
		$this->mockImage->expects($this->once())->method('getThumbnail')->with(456, 100, \TYPO3\Media\Domain\Model\Image::RATIOMODE_INSET)->will($this->returnValue($this->mockThumbnail));
		$this->viewHelper->render($this->mockImage, 456, NULL, FALSE, TRUE);
	}

	/**
	 * @test
	 */
	public function thumbnailHeightMightExceedImageHeightIfAllowUpScalingIsTrue() {
		$this->mockImage->expects($this->once())->method('getThumbnail')->with(100, 456, \TYPO3\Media\Domain\Model\Image::RATIOMODE_INSET)->will($this->returnValue($this->mockThumbnail));
		$this->viewHelper->render($this->mockImage, NULL, 456, FALSE, TRUE);
	}
}
