<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * An image variant that has a relation to the original image
 *
 * TODO: Remove duplicate code at Image and ImageVariant, either via underlying Abstract Class or once Mixins/Traits are available
 * Note: This is neither an entity nor a value object, ImageVariants won't be persisted on their own.
 */
class ImageVariant implements ImageInterface {

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Media\Domain\Service\ImageService
	 * @Flow\Inject
	 */
	protected $imageService;

	/**
	 * @var \TYPO3\Media\Domain\Model\ImageInterface
	 */
	protected $originalImage;

	/**
	 * @var array
	 */
	protected $processingInstructions = array();

	/**
	* @var \TYPO3\Flow\Resource\Resource
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
	 * alias for this variant which makes its identification easier for reuse.
	 *
	 * @var string
	 */
	protected $alias;

	/**
	 * @param \TYPO3\Media\Domain\Model\ImageInterface $originalImage
	 * @param array $processingInstructions
	 * @param string $alias An alias for this variant which makes its identification easier for reuse.
	 */
	public function __construct(ImageInterface $originalImage, array $processingInstructions, $alias = NULL) {
		if ($originalImage instanceof ImageVariant) {
			$this->originalImage = $originalImage->getOriginalImage();
		} else {
			$this->originalImage = $originalImage;
		}
		$this->processingInstructions = $processingInstructions;
		$this->alias = $alias;
	}

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->resource = $this->imageService->transformImage($this->originalImage, $this->processingInstructions);
		$imageSize = getimagesize($this->resource->getUri());
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
	 * Resource of the variant
	 *
	 * @return \TYPO3\Flow\Resource\Resource
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
	 * Edge / aspect ratio of the image
	 *
	 * @param boolean $respectOrientation If false (the default), orientation is disregarded and always a value >= 1 is returned (like usual in "4 / 3" or "16 / 9")
	 * @return float
	 */
	public function getAspectRatio($respectOrientation = FALSE) {
		$aspectRatio = $this->getWidth() / $this->getHeight();
		if ($respectOrientation === FALSE && $aspectRatio < 1) {
			$aspectRatio = 1 / $aspectRatio;
		}

		return $aspectRatio;
	}

	/**
	 * Orientation of this image, i.e. portrait, landscape or square
	 *
	 * @return string One of this interface's ORIENTATION_* constants.
	 */
	public function getOrientation() {
		$aspectRatio = $this->getAspectRatio(TRUE);
		if ($aspectRatio > 1) {
			return ImageInterface::ORIENTATION_LANDSCAPE;
		}
		elseif ($aspectRatio < 1) {
			return ImageInterface::ORIENTATION_PORTRAIT;
		}
		else {
			return ImageInterface::ORIENTATION_SQUARE;
		}
	}

	/**
	 * Whether this image is square aspect ratio and therefore has a square orientation
	 *
	 * @return boolean
	 */
	public function isOrientationSquare() {
		return $this->getOrientation() === ImageInterface::ORIENTATION_SQUARE;
	}

	/**
	 * Whether this image is in landscape orientation
	 *
	 * @return boolean
	 */
	public function isOrientationLandscape() {
		return $this->getOrientation() === ImageInterface::ORIENTATION_LANDSCAPE;
	}

	/**
	 * Whether this image is in portrait orientation
	 *
	 * @return boolean
	 */
	public function isOrientationPortrait() {
		return $this->getOrientation() === ImageInterface::ORIENTATION_PORTRAIT;
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
	 * If maximum width/height is not specified or exceed the original images size,
	 * width/height of the original image is used
	 *
	 * Note: The image variant that will be created is intentionally not added to the
	 * imageVariants collection of the original image. If you want to create a persisted
	 * image variant, use createImageVariant() instead.
	 *
	 * @param integer $maximumWidth
	 * @param integer $maximumHeight
	 * @param string $ratioMode
	 * @return \TYPO3\Media\Domain\Model\ImageVariant
	 * @see \TYPO3\Media\Domain\Model\Image::getThumbnail
	 */
	public function getThumbnail($maximumWidth = NULL, $maximumHeight = NULL, $ratioMode = ImageInterface::RATIOMODE_INSET) {
		return $this->originalImage->getThumbnail($maximumWidth, $maximumHeight, $ratioMode);
	}

	/**
	 * @return string
	 */
	public function getAlias() {
		return $this->alias;
	}

	/**
	 * @return array
	 */
	public function __sleep() {
		return array('originalImage', 'processingInstructions', 'alias');
	}

	/**
	 * @return void
	 */
	public function __wakeup() {
		if ($this->originalImage->getResource() === NULL) {
			// hack for working around a bug that is caused by serialization under some (unknown) circumstances
			$this->originalImage = $this->persistenceManager->getObjectByIdentifier(\TYPO3\Flow\Reflection\ObjectAccess::getProperty($this->originalImage, 'Persistence_Object_Identifier', TRUE), 'TYPO3\Media\Domain\Model\Image');
		}
		$this->initializeObject(ObjectManagerInterface::INITIALIZATIONCAUSE_RECREATED);
	}

	/**
	 * Setting the image resource on an ImageVariant is not allowed, this method will
	 * throw a RuntimeException.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource
	 * @return void
	 * @throws \RuntimeException
	 */
	public function setResource(\TYPO3\Flow\Resource\Resource $resource) {
		throw new \RuntimeException('Setting the resource on an ImageVariant is not supported.', 1366627480);
	}

	/**
	 * Setting the title on an ImageVariant is not allowed, this method will throw a
	 * RuntimeException.
	 *
	 * @param string $title
	 * @return void
	 * @throws \RuntimeException
	 */
	public function setTitle($title) {
		throw new \RuntimeException('Setting the title on an ImageVariant is not supported.', 1366627475);
	}

	/**
	 * Add a single tag to this asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @return void
	 */
	public function addTag(Tag $tag) {
		throw new \RuntimeException('Adding a tag on an ImageVariant is not supported.', 1371237593);
	}

	/**
	 * Set the tags assigned to this asset
	 *
	 * @param \Doctrine\Common\Collections\Collection $tags
	 * @return void
	 */
	public function setTags(\Doctrine\Common\Collections\Collection $tags) {
		throw new \RuntimeException('Settings tags on an ImageVariant is not supported.', 1371237597);
	}


}
