<?php
namespace TYPO3\Media\Tests\Unit\Service;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the ImageService
 */
class ImageServiceTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Media\Service\ImageService
     */
    protected $service;

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
    public function setUp()
    {
        $this->service = new \TYPO3\Media\Service\ImageService();

        $this->mockImage = $this->getMock('TYPO3\Media\Domain\Model\ImageInterface');
        $this->mockImage->expects($this->any())->method('getWidth')->will($this->returnValue(100));
        $this->mockImage->expects($this->any())->method('getHeight')->will($this->returnValue(100));
        $this->mockThumbnail = $this->getMockBuilder('TYPO3\Media\Domain\Model\ImageVariant')->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function ratioModeDefaultsToInset()
    {
        $this->mockImage->expects($this->once())->method('getThumbnail')->with(50, 100, \TYPO3\Media\Domain\Model\Image::RATIOMODE_INSET)->will($this->returnValue($this->mockThumbnail));
        $this->service->getImageThumbnailImage($this->mockImage, 50);
    }

    /**
     * @test
     */
    public function ratioModeIsOutboundIfAllowCroppingIsTrue()
    {
        $this->mockImage->expects($this->once())->method('getThumbnail')->with(50, 100, \TYPO3\Media\Domain\Model\Image::RATIOMODE_OUTBOUND)->will($this->returnValue($this->mockThumbnail));
        $this->service->getImageThumbnailImage($this->mockImage, 50, null, true);
    }

    /**
     * @test
     */
    public function thumbnailWidthDoesNotExceedImageWithByDefault()
    {
        $this->mockImage->expects($this->never())->method('getThumbnail');
        $this->service->getImageThumbnailImage($this->mockImage, 456, null);
    }

    /**
     * @test
     */
    public function thumbnailHeightDoesNotExceedImageHeightByDefault()
    {
        $this->mockImage->expects($this->never())->method('getThumbnail');
        $this->service->getImageThumbnailImage($this->mockImage, null, 123);
    }

    /**
     * @test
     */
    public function thumbnailWidthMightExceedImageWithIfAllowUpScalingIsTrue()
    {
        $this->mockImage->expects($this->once())->method('getThumbnail')->with(456, 100, \TYPO3\Media\Domain\Model\Image::RATIOMODE_INSET)->will($this->returnValue($this->mockThumbnail));
        $this->service->getImageThumbnailImage($this->mockImage, 456, null, false, true);
    }

    /**
     * @test
     */
    public function thumbnailHeightMightExceedImageHeightIfAllowUpScalingIsTrue()
    {
        $this->mockImage->expects($this->once())->method('getThumbnail')->with(100, 456, \TYPO3\Media\Domain\Model\Image::RATIOMODE_INSET)->will($this->returnValue($this->mockThumbnail));
        $this->service->getImageThumbnailImage($this->mockImage, null, 456, false, true);
    }
}
