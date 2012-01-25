<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Media".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An image variant that has a relation to the original image
 *
 * Note: This is neither an entity nor a value object, ImageVariants won't be persisted on their own.
 */
class ImageVariant implements \TYPO3\Media\Domain\Model\ImageInterface {

	/**
	 * @var \TYPO3\Media\Domain\Service\ImageService
	 * @FLOW3\Inject
	 */
	protected $imageService;

	/**
	 * @var \TYPO3\Media\Domain\Model\Image
	 */
	protected $originalImage;

	/**
	 * @var array
	 */
	protected $processingInstructions = array();

	/**
	 * @var \TYPO3\FLOW3\Resource\Resource
	 */
	protected $resource;

	/**
	 * @var integer
	 */
	protected $width;

	/**
	 * @var integer
	 */
	protected $height;

	/**
	 * one of PHPs IMAGETYPE_* constants
	 *
	 * @var integer
	 */
	protected $type;

	/**
	 * @param \TYPO3\Media\Domain\Model\Image $originalImage
	 * @param array $processingInstructions
	 */
	public function __construct(\TYPO3\Media\Domain\Model\Image $originalImage, array $processingInstructions) {
		$this->originalImage = $originalImage;
		$this->processingInstructions = $processingInstructions;
	}

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->resource = $this->imageService->transformImage($this->originalImage, $this->processingInstructions);
		$imageSize = getimagesize('resource://' . $this->resource->getResourcePointer()->getHash());
		$this->width = (integer)$imageSize[0];
		$this->height = (integer)$imageSize[1];
		$this->type = (integer)$imageSize[2];
	}

	/**
	 * @return \TYPO3\Media\Domain\Model\ImageInterface
	 */
	public function getOriginalImage() {
		return $this->originalImage;
	}

	/**
	 * Resource of the original file of this variant
	 *
	 * @return \TYPO3\FLOW3\Resource\Resource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Width of the image in pixels
	 *
	 * @return integer
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * Height of the image in pixels
	 *
	 * @return integer
	 */
	public function getHeight() {
		return $this->height;
	}

	/**
	 * One of PHPs IMAGETYPE_* constants that reflects the image type
	 * This will return the type of the original image as this should not be different in image variants
	 *
	 * @see http://php.net/manual/image.constants.php
	 * @return integer
	 */
	public function getType() {
		return $this->originalImage->getType();
	}

	/**
	 * File extension of the image without leading dot.
	 * This will return the file extension of the original image as this should not be different in image variants
	 *
	 * @return string
	 */
	public function getFileExtension() {
		return $this->originalImage->getFileExtension();
	}

	/**
	 * Returns the title of the original image
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->originalImage->getTitle();
	}

	/**
	 * Returns the processing instructions that were used to create this image variant.
	 *
	 * @return string
	 * @see \TYPO3\Media\Domain\Service\ImageService::transformImage()
	 */
	public function getProcessingInstructions() {
		return $this->processingInstructions;
	}

	/**
	 * Creates a thumbnail of the original image
	 *
	 * @param integer $maximumWidth
	 * @param integer $maximumHeight
	 * @return \TYPO3\Media\Domain\Model\ImageVariant
	 * @see \TYPO3\Media\Domain\Model\Image::getThumbnail
	 */
	public function getThumbnail($maximumWidth = NULL, $maximumHeight = NULL) {
		return $this->originalImage->getThumbnail($maximumWidth, $maximumHeight);
	}

	/**
	 * @return array
	 */
	public function __sleep() {
		return array('originalImage', 'processingInstructions');
	}
}

?>